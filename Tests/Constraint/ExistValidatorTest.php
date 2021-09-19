<?php

declare(strict_types=1);

namespace FRZB\Component\ExtendedValidation\Tests\Constraint;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\Persistence\Mapping\MappingException;
use FRZB\Component\ExtendedValidation\Constraint\Exist;
use FRZB\Component\ExtendedValidation\Constraint\ExistValidator;
use FRZB\Component\ExtendedValidation\CountRepositoryInterface as CountRepository;
use FRZB\Component\ExtendedValidation\Exception\NoEntityAnnotationException;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\Entity\Employee;
use FRZB\Component\ExtendedValidation\Tests\Fixtures\TestConstants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface as ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface as ConstraintViolationBuilder;

/**
 * @internal
 *
 * @covers \FRZB\Component\ExtendedValidation\Constraint\ExistValidator
 * @covers \FRZB\Component\ExtendedValidation\Constraint\Exist
 */
class ExistValidatorTest extends TestCase
{
    /** @psalm-var MockObject&CountRepository */
    private MockObject $countRepository;

    /** @psalm-var MockObject&AnnotationReader */
    private MockObject $annotationReader;

    /** @psalm-var MockObject&ExecutionContext */
    private MockObject $executionContext;

    /** @psalm-var MockObject&ConstraintViolationBuilder */
    private MockObject $constraintViolationBuilder;

    private ExistValidator $existValidator;

    private Exist $constraint;
    private Entity $annotation;

    protected function setUp(): void
    {
        $this->countRepository = $this->createMock(CountRepository::class);
        $this->annotationReader = $this->createMock(AnnotationReader::class);
        $this->executionContext = $this->createMock(ExecutionContext::class);
        $this->constraintViolationBuilder = $this->createMock(ConstraintViolationBuilder::class);

        $this->existValidator = new ExistValidator($this->countRepository, $this->annotationReader);
        $this->existValidator->initialize($this->executionContext);

        $this->constraint = new Exist(Employee::class, 'email');
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

        array_walk($values, fn (mixed $value) => $this->existValidator->validate($value, $this->constraint));
    }

    public function testValidateWhenInvalidEntityClassGiven(): void
    {
        $this->expectException(NoEntityAnnotationException::class);
        $this->existValidator->validate(TestConstants::EMAIL, new Exist('UndefinedClass', 'email'));
    }

    public function testValidateWhenInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->existValidator->validate(new \stdClass(), $this->constraint);
        $this->existValidator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWhenUnexpectedTypeException(): void
    {
//        MappingException::class
        $this->expectException(UnexpectedTypeException::class);
        $this->existValidator->validate(TestConstants::EMAIL, new Email());
    }

    public function testValidateWhenEntityHasNoEntityAnnotation(): void
    {
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn(null);

        $this->expectException(NoEntityAnnotationException::class);
        $this->existValidator->validate(TestConstants::EMAIL, $this->constraint);
    }

    public function testValidateWhenEntityNotExists(): void
    {
        $this->countRepository->expects(self::once())->method('getCountOf')->willReturn(0);
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->executionContext->expects(self::once())->method('buildViolation')->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder->expects(self::exactly(3))->method('setParameter')->willReturnSelf();
        $this->constraintViolationBuilder->expects(self::once())->method('addViolation')->willReturnSelf();

        $this->existValidator->validate([TestConstants::EMAIL], $this->constraint);
    }

    public function testValidateWhenEntityIsExists(): void
    {
        $this->countRepository->expects(self::once())->method('getCountOf')->willReturn(1);
        $this->annotationReader->expects(self::once())->method('getClassAnnotation')->willReturn($this->annotation);
        $this->executionContext->expects(self::never())->method('buildViolation')->willReturn($this->constraintViolationBuilder);
        $this->constraintViolationBuilder->expects(self::never())->method('setParameter')->willReturnSelf();
        $this->constraintViolationBuilder->expects(self::never())->method('addViolation')->willReturnSelf();

        $this->existValidator->validate(TestConstants::EMAIL, $this->constraint);
    }
}
