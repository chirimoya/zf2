<?php

namespace ZendTest\Di;

use Zend\Debug;

use Zend\Di\DependencyInjector,
    PHPUnit_Framework_TestCase as TestCase;

class DependencyInjectorTest extends TestCase
{

    public function testDependencyInjectorHasBuiltInImplementations()
    {
        $di = new DependencyInjector();
        $this->assertInstanceOf('Zend\Di\InstanceManager', $di->getInstanceManager());
        $this->assertInstanceOf('Zend\Di\Definition', $di->getDefinition());
        $this->assertInstanceOf('Zend\Di\Definition\RuntimeDefinition', $di->getDefinition());
    }
    
    public function testPassingInvalidDefinitionRaisesException()
    {
        $di = new DependencyInjector();
        
        $this->setExpectedException('PHPUnit_Framework_Error');
        $di->setDefinition(array('foo'));
    }
    
    public function testGetRetrievesObjectWithMatchingClassDefinition()
    {
        $di = new DependencyInjector();
        $obj = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj);
    }
    
    public function testGetRetrievesSameInstanceOnSubsequentCalls()
    {
        $di = new DependencyInjector();
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertSame($obj1, $obj2);
    }
    
    public function testGetThrowsExceptionWhenUnknownClassIsUsed()
    {
        $di = new DependencyInjector();
        
        $this->setExpectedException('Zend\Di\Exception\ClassNotFoundException', 'could not be located in');
        $obj1 = $di->get('ZendTest\Di\TestAsset\NonExistentClass');
    }
    
    public function testGetThrowsExceptionWhenMissingParametersAreEncountered()
    {
        $di = new DependencyInjector();
        
        $this->setExpectedException('Zend\Di\Exception\MissingPropertyException', 'Missing parameter named ');
        $obj1 = $di->get('ZendTest\Di\TestAsset\BasicClassWithParam');
    }
    
    public function testNewInstanceReturnsDifferentInstances()
    {
        $di = new DependencyInjector();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertNotSame($obj1, $obj2);
    }
    
    public function testNewInstanceReturnsInstanceThatIsSharedWithGet()
    {
        $di = new DependencyInjector();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass');
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertSame($obj1, $obj2);
    }
    
    public function testNewInstanceReturnsInstanceThatIsNotSharedWithGet()
    {
        $di = new DependencyInjector();
        $obj1 = $di->newInstance('ZendTest\Di\TestAsset\BasicClass', array(), false);
        $obj2 = $di->get('ZendTest\Di\TestAsset\BasicClass');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj1);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\BasicClass', $obj2);
        $this->assertNotSame($obj1, $obj2);
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testGetWillResolveConstructorInjectionDependencies()
    {
        $di = new DependencyInjector();
        $b = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testGetWillResolveConstructorInjectionDependenciesAndInstanceAreTheSame()
    {
        $di = new DependencyInjector();
        $b = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);
        
        $b2 = $di->get('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b2);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b2->a);
        
        $this->assertSame($b, $b2);
        $this->assertSame($b->a, $b2->a);
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependencies()
    {
        $di = new DependencyInjector();
        $b = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\A', $b->a);
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependenciesWithProperties()
    {
        $di = new DependencyInjector();
        
        $im = $di->getInstanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\ConstructorInjection\X', array('one' => 1, 'two' => 2));
        
        $y = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\Y');
        $this->assertEquals(1, $y->x->one);
        $this->assertEquals(2, $y->x->two);
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillThrowExceptionOnConstructorInjectionDependencyWithMissingParameter()
    {
        $di = new DependencyInjector();
        
        $this->setExpectedException('Zend\Di\Exception\MissingPropertyException', 'Missing parameter named one');
        $b = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\X');
    }
    
    /**
     * @group ConstructorInjection
     */
    public function testNewInstanceWillResolveConstructorInjectionDependenciesWithMoreThan2Dependencies()
    {
        $di = new DependencyInjector();
        
        $im = $di->getInstanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\ConstructorInjection\X', array('one' => 1, 'two' => 2));
        
        $z = $di->newInstance('ZendTest\Di\TestAsset\ConstructorInjection\Z');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\Y', $z->y);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\ConstructorInjection\X', $z->y->x);
    }
    
    /**
     * @group SetterInjection
     */
    public function testGetWillResolveSetterInjectionDependencies()
    {
        $di = new DependencyInjector();
        $b = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }
    
    /**
     * @group SetterInjection
     */
    public function testGetWillResolveSetterInjectionDependenciesAndInstanceAreTheSame()
    {
        $di = new DependencyInjector();
        $b = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
        
        $b2 = $di->get('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b2);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b2->a);
        
        $this->assertSame($b, $b2);
        $this->assertSame($b->a, $b2->a);
    }
    
    /**
     * @group SetterInjection
     */
    public function testNewInstanceWillResolveSetterInjectionDependencies()
    {
        $di = new DependencyInjector();
        $b = $di->newInstance('ZendTest\Di\TestAsset\SetterInjection\B');
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\B', $b);
        $this->assertInstanceOf('ZendTest\Di\TestAsset\SetterInjection\A', $b->a);
    }
    
    /**
     * @group SetterInjection
     */
    public function testNewInstanceWillResolveSetterInjectionDependenciesWithProperties()
    {
        $di = new DependencyInjector();
        
        $im = $di->getInstanceManager();
        $im->setParameters('ZendTest\Di\TestAsset\SetterInjection\X', array('one' => 1, 'two' => 2));
        
        $y = $di->newInstance('ZendTest\Di\TestAsset\SetterInjection\Y');
        $this->assertEquals(1, $y->x->one);
        $this->assertEquals(2, $y->x->two);
    }
    
    /**
     * Test for Circular Dependencies (case 1)
     * 
     * A->B, B->A
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionOnBasicCircularDependency()
    {
        $di = new DependencyInjector();

        $this->setExpectedException('Zend\Di\Exception\CircularDependencyException');
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\A');
    }
    
    /**
     * Test for Circular Dependencies (case 2)
     * 
     * C->D, D->E, E->C
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionOnThreeLevelCircularDependency()
    {
        $di = new DependencyInjector();

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\E depends on ZendTest\Di\TestAsset\CircularClasses\C and viceversa'
        );
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\C');
    }
    
    /**
     * Test for Circular Dependencies (case 2)
     * 
     * C->D, D->E, E->C
     * @group CircularDependencyCheck
     */
    public function testNewInstanceThrowsExceptionWhenEnteringInMiddleOfCircularDependency()
    {
        $di = new DependencyInjector();

        $this->setExpectedException(
            'Zend\Di\Exception\CircularDependencyException',
            'Circular dependency detected: ZendTest\Di\TestAsset\CircularClasses\C depends on ZendTest\Di\TestAsset\CircularClasses\D and viceversa'
        );
        $di->newInstance('ZendTest\Di\TestAsset\CircularClasses\D');
    }
    
    /**
     * Fix for PHP bug in is_subclass_of
     * 
     * @see https://bugs.php.net/bug.php?id=53727
     */
    public function testNewInstanceWillUsePreferredClassForInterfaceHints()
    {
        $di = new DependencyInjector();
        $di->getInstanceManager()->addTypePreference(
            'ZendTest\Di\TestAsset\PreferredImplClasses\A',
            'ZendTest\Di\TestAsset\PreferredImplClasses\BofA'
        );
        
        $c = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\C');
        $a = $c->a;
        $this->assertInstanceOf('ZendTest\Di\TestAsset\PreferredImplClasses\BofA', $a);
        $d = $di->get('ZendTest\Di\TestAsset\PreferredImplClasses\D');
        $this->assertSame($a, $d->a);
    }
    
    public function testNewInstanceWillRunArbitraryMethodsAccordingToConfiguration()
    {
        $di = new DependencyInjector();
        $im = $di->getInstanceManager();
        $im->setMethods('ZendTest\Di\TestAsset\ConfigParameter\A', array(
            'setSomeInt' => array('value' => 5),
            'injectM' => array('m' => 10)
        ));
        $b = $di->newInstance('ZendTest\Di\TestAsset\ConfigParameter\B');
        $this->assertEquals(5, $b->a->someInt);
        $this->assertEquals(10, $b->a->m);
    }
    
}
