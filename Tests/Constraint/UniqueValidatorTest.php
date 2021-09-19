<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Tests\Constraint;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\EntityManagerInterface as EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\Mapping\MappingException;
use FRZB\Component\ExtendedValidation\Constraint\Unique;
use FRZB\Component\ExtendedValidation\Constraint\UniqueValidator;
use FRZB\Component\ExtendedValidation\CountRepositoryInterface as CountRepository;
use FRZB\Component\ExtendedValidation\Exception\NoEntityAnnotationException;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\Entity\Employee;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\Entity\EmployeeRepository;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\TestConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface as ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface as ConstraintViolationBuilder;

/**
 * @internal
 *
 * @covers \FRZB\Component\ExtendedValidation\Constraint\UniqueValidator
 * @covers \FRZB\Component\ExtendedValidation\Constraint\Unique
 */
class UniqueValidatorTest extends TestCase
{
    /**
     * @psalm-var MockObject&EntityManager
     */
    private MockObject $entityManager;

    /** @psalm-var MockObject&CountRepository */
    private MockObject $countRepository;

    /** @psalm-var MockObject&EmployeeRepository */
    private MockObject $employeeRepository;

    /** @psalm-var MockObject&AnnotationReader */
    private MockObject $annotationReader;

    /** @psalm-var MockObject&ExecutionContext */
    private MockObject $executionContext;

    /** @psalm-var MockObject&ConstraintViolationBuilder */
    private MockObject $constraintViolationBuilder;

    private UniqueValidator $uniqueValidator;

    private Unique $constraint;
    private Entity $annotation;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->countRepository = $this->createMock(CountRepository::class);
        $this->employeeRepository = $this->createMock(EmployeeRepository::class);
        $this->annotationReader = $this->createMock(AnnotationReader::class);
        $this->executionContext = $this->createMock(ExecutionContext::class);
        $this->constraintViolationBuilder = $this->createMock(ConstraintViolationBuilder::class);

        $this->uniqueValidator = new UniqueValidator(
            $this->entityManager,
            $this->countRepository,
            $this->annotationReader,
            new RequestStack()
        );
        $this->uniqueValidator->initialize($this->executionContext);

        $this->constraint = new Unique(Employee::class, 'email');
        $this->annotation = new Entity();
    }

    public function testValidateWhenEmptyValue(): void
    {
        $values = [TestConstants::NULL_VALUE, TestConstants::EMPTY_VALUE];

        $this->countRepository->expects(self::never())->method('getCountOf')->willReturn(0);
        $this->annotationReader->expects(self::never())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->executionContext->expects(self::never())->method('buildViolation')->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder->expects(self::never())->method('setParameter')->willReturnSelf();
        $this->constraintViolationBuilder->expects(self::never())->method('addViolation')->willReturnSelf();

        array_walk($values, fn (mixed $value) => $this->uniqueValidator->validate($value, $this->constraint));
    }

    public function testValidateWhenInvalidEntityClassGiven(): void
    {
        $this->expectException(NoEntityAnnotationException::class);
        $this->uniqueValidator->validate(TestConstants::EMAIL, new Unique('UndefinedClass', 'email'));
    }

    public function testValidateWhenInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->uniqueValidator->validate(new \stdClass(), $this->constraint);
        $this->uniqueValidator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWhenUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->uniqueValidator->validate(TestConstants::EMAIL, new Email());
    }

    public function testValidateWhenEntityHasNoEntityAnnotation(): void
    {
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn(null);

        $this->expectException(NoEntityAnnotationException::class);
        $this->uniqueValidator->validate(TestConstants::EMAIL, $this->constraint);
    }

    public function testValidateWhenMappingException(): void
    {
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->entityManager->expects(self::once())->method('getRepository')->willThrowException(new MappingException());

        $this->uniqueValidator->validate(TestConstants::EMAIL, $this->constraint);
    }

    public function testValidateWhenEntityNotExists(): void
    {
        $this->countRepository->expects(self::once())->method('getCountOf')->willReturn(1);
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->executionContext->expects(self::once())->method('buildViolation')->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder->expects(self::exactly(3))->method('setParameter')->willReturnSelf();
        $this->constraintViolationBuilder->expects(self::once())->method('addViolation')->willReturnSelf();

        $this->uniqueValidator->validate(TestConstants::EMAIL, $this->constraint);
    }

    /**
     * @dataProvider existProvider
     */
    public function testValidateWhenEntityIsExistsWithCustomCountRepository(Unique $constraint, int $expectsCount): void
    {
        $this->employeeRepository->expects(self::once())->method('getCountOf')->willReturn($expectsCount);
        $this->countRepository->expects(self::never())->method('getCountOf');
        $this->entityManager->expects(self::once())->method('getRepository')->willReturn($this->employeeRepository);
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->executionContext->expects(self::never())->method('buildViolation')->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder->expects(self::never())->method('setParameter')->willReturnSelf();
        $this->constraintViolationBuilder->expects(self::never())->method('addViolation')->willReturnSelf();

        $this->uniqueValidator->validate(TestConstants::EMAIL, $constraint);
    }

    public function existProvider(): iterable
    {
        yield 'create' => [
            'constraint' => new Unique(Employee::class, 'email', type: Unique::TYPE_CREATE),
            'expectsCount' => UniqueValidator::ALLOWED_COUNT_WHEN_CREATE,
        ];

        yield 'update' => [
            'constraint' => new Unique(Employee::class, 'email', type: Unique::TYPE_UPDATE),
            'expectsCount' => UniqueValidator::ALLOWED_COUNT_WHEN_UPDATE,
        ];
    }
}
