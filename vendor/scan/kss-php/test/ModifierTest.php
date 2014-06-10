<?php

namespace Scan\Test;

class ModifierTest extends \PHPUnit_Framework_TestCase
{
    protected static $modifier;
    protected static $extenderModifier;

    public static function setUpBeforeClass()
    {
        $name = '.modifier';
        $description = 'This is a test modifier';
        self::$modifier = new \Scan\Kss\Modifier($name, $description);
        self::$modifier->setMarkup('<div class="plainClass $modifierClass">test</div>');

        $name = '.extenderModifier @extend .modifier';
        $description = 'This is a test modifier that extends from .modifier';
        self::$extenderModifier = new \Scan\Kss\Modifier($name, $description);
        self::$extenderModifier->setMarkup('<div class="modifier $modifierClass">test</div>');
    }

    /**
     * @test
     */
    public function testGetName()
    {
        $this->assertEquals('.modifier', self::$modifier->getName());
    }

    /**
     * @test
     */
    public function getExtenderName()
    {
        $this->assertEquals('.extenderModifier', self::$extenderModifier->getName());
    }

    /**
     * @test
     */
    public function getDescription()
    {
        $this->assertEquals('This is a test modifier', self::$modifier->getDescription());
    }

    /**
     * @test
     */
    public function getExtenderDescription()
    {
        $this->assertEquals(
            'This is a test modifier that extends from .modifier',
            self::$extenderModifier->getDescription()
        );
    }

    /**
     * @test
     */
    public function isNotExtender()
    {
        $this->assertFalse(self::$modifier->isExtender());
    }
    /**
     * @test
     */
    public function isExtender()
    {
        $this->assertTrue(self::$extenderModifier->isExtender());
    }

    /**
     * @test
     */
    public function getClassName()
    {
        $this->assertEquals('modifier', self::$modifier->getClassName());
    }

    /**
     * @test
     */
    public function getExtenderClassName()
    {
        $this->assertEquals('extenderModifier', self::$extenderModifier->getClassName());
    }

    /**
     * @test
     */
    public function getExtendedClassName()
    {
        $this->assertEquals('', self::$modifier->getExtendedClassName());
    }

    /**
     * @test
     */
    public function getExtenderExtendedClassName()
    {
        $this->assertEquals('modifier', self::$extenderModifier->getExtendedClassName());
    }

    /**
     * @test
     */
    public function getExampleHtml()
    {
        $html = self::$modifier->getExampleHtml();
        $expected = '<div class="plainClass modifier">test</div>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExtenderExampleHtml()
    {
        $html = self::$extenderModifier->getExampleHtml();
        $expected = '<div class="extenderModifier ">test</div>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExampleHtmlSpecified()
    {
        $exampleHtml = '<span class="$modifierClass">test2</span>';
        $html = self::$modifier->getExampleHtml($exampleHtml);
        $expected = '<span class="modifier">test2</span>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExtenderExampleHtmlSpecified()
    {
        $exampleHtml = '<span class="modifier $modifierClass">test2</span>';
        $html = self::$extenderModifier->getExampleHtml($exampleHtml);
        $expected = '<span class="extenderModifier ">test2</span>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExtenderExampleHtmlWithSimilarElementName()
    {
        $name = '.extenderModifier @extend .button';
        $description = 'This is a test modifier that extends from .button';
        $extenderModifier = new \Scan\Kss\Modifier($name, $description);

        $exampleHtml = '<button class="modifier button $modifierClass">test3</button>';
        $html = $extenderModifier->getExampleHtml($exampleHtml);
        $expected = '<button class="modifier extenderModifier ">test3</button>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExtenderExampleHtmlWithSimilarElementNameAtStartOfAttribute()
    {
        $name = '.extenderModifier @extend .button';
        $description = 'This is a test modifier that extends from .button';
        $extenderModifier = new \Scan\Kss\Modifier($name, $description);

        // Note that this also preserves the similar class name
        // 'button-success'
        $exampleHtml = '<button class="button button-success $modifierClass">test3</button>';
        $html = $extenderModifier->getExampleHtml($exampleHtml);
        $expected = '<button class="extenderModifier button-success ">test3</button>';
        $this->assertEquals($expected, $html);
    }

    /**
     * @test
     */
    public function getExtenderExampleHtmlWithSimilarElementNameAtEndOfAttribute()
    {
        $name = '.extenderModifier @extend .button';
        $description = 'This is a test modifier that extends from .button';
        $extenderModifier = new \Scan\Kss\Modifier($name, $description);

        $exampleHtml = '<button class="button-success $modifierClass button">test3</button>';
        $html = $extenderModifier->getExampleHtml($exampleHtml);
        $expected = '<button class="button-success  extenderModifier">test3</button>';
        $this->assertEquals($expected, $html);
    }
}
