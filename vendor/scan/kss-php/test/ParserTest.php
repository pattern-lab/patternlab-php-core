<?php

namespace Scan\Test;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function parseScss()
    {
        $parser = new \Scan\Kss\Parser('test/fixtures/scss');
        $this->assertEquals('Your standard form button.', $parser->getSection('2.1.1')->getDescription());
        return $parser;
    }

    /**
     * @test
     */
    public function parseSass()
    {
        $parser = new \Scan\Kss\Parser('test/fixtures/sass');
        $this->assertEquals(
            'A button suitable for giving stars to someone.',
            $parser->getSection('2.2.1')->getDescription()
        );
        return $parser;
    }

    /**
     * @test
     */
    public function parseLess()
    {
        $parser = new \Scan\Kss\Parser('test/fixtures/less');
        $this->assertEquals('Your standard form button.', $parser->getSection('2.1.1')->getDescription());
        return $parser;
    }

    /**
     * @test
     */
    public function parseCss()
    {
        $parser = new \Scan\Kss\Parser('test/fixtures/css');
        $this->assertEquals('Your standard form button.', $parser->getSection('2.1.1')->getDescription());
        return $parser;
    }

    /**
     * @test
     * @depends parseSass
     */
    public function parseMultiLineSass($parser)
    {
        $this->assertEquals('Your standard form button.', $parser->getSection('2.1.1')->getDescription());
    }

    /**
     * @test
     * @depends parseScss
     */
    public function parseNestedScss($parser)
    {
        $this->assertEquals('Your standard form element.', $parser->getSection('3.0.0')->getDescription());
        $this->assertEquals('Your standard text input box.', $parser->getSection('3.0.1')->getDescription());
    }

    /**
     * @test
     * @depends parseSass
     */
    public function parseNestedSass($parser)
    {
        $this->assertEquals('Your standard form element.', $parser->getSection('3.0.0')->getDescription());
        $this->assertEquals('Your standard text input box.', $parser->getSection('3.0.1')->getDescription());
    }

    /**
     * @test
     * @depends parseLess
     */
    public function parseNestedLess($parser)
    {
        $this->assertEquals('Your standard form element.', $parser->getSection('3.0.0')->getDescription());
        $this->assertEquals('Your standard text input box.', $parser->getSection('3.0.1')->getDescription());
    }

    /**
     * @test
     * @depends parseScss
     */
    public function getSection($parser)
    {
        $this->assertEquals('2.1.1', $parser->getSection('2.1.1')->getReference());
    }

    /**
     * @test
     * @expectedException Scan\Kss\Exception\UnexpectedValueException
     * @depends parseScss
     */
    public function getSectionNotFound($parser)
    {
        $this->assertEmpty($parser->getSection('200.1.1')->getReference());
    }

    /**
     * @test
     * @depends parseScss
     */
    public function getSections($parser)
    {
        $this->assertCount(5, $parser->getSections());
    }

    /**
     * @test
     * @depends parseScss
     */
    public function getTopLevelSections($parser)
    {
        $expectedSections = array('2', '3.0.0');
        $sections = $parser->getTopLevelSections();
        $this->assertCount(count($expectedSections), $sections);
        $x = 0;
        foreach ($sections as $section) {
            $this->assertEquals($expectedSections[$x], $section->getReference());
            ++$x;
        }
    }

    /**
     * @test
     * @depends parseScss
     */
    public function getSectionChildren($parser)
    {
        $expectedSections = array('2.1.1', '2.2.1');
        $sections = $parser->getSectionChildren('2');
        $this->assertCount(count($expectedSections), $sections);
        $x = 0;
        foreach ($sections as $section) {
            $this->assertEquals($expectedSections[$x], $section->getReference());
            ++$x;
        }

        $expectedSections = array('3.0.1');
        $sections = $parser->getSectionChildren('3');
        $this->assertCount(count($expectedSections), $sections);
        $x = 0;
        foreach ($sections as $section) {
            $this->assertEquals($expectedSections[$x], $section->getReference());
            ++$x;
        }
    }

    /**
     * @test
     * @depends parseScss
     */
    public function getSectionChildrenWithDepth($parser)
    {
        $expectedSections = array();
        $sections = $parser->getSectionChildren('3', 0);
        $this->assertCount(count($expectedSections), $sections);
        $x = 0;
        foreach ($sections as $section) {
            $this->assertEquals($expectedSections[$x], $section->getReference());
            ++$x;
        }

        $expectedSections = array('3.0.1');
        $sections = $parser->getSectionChildren('3', 2);
        $this->assertCount(count($expectedSections), $sections);
        $x = 0;
        foreach ($sections as $section) {
            $this->assertEquals($expectedSections[$x], $section->getReference());
            ++$x;
        }
    }

    /**
     * @test
     */
    public function isKssBlock()
    {
        $comment = '// This is a style comment
//
// .modifier1
// .modifier2
//
// Styleguide 1.2.3
        ';

        $this->assertTrue(\Scan\Kss\Parser::isKssBlock($comment));
    }

    /**
     * @test
     */
    public function isNotKssBlock()
    {
        $comment = '// This is just a normal comment
// It has two lines
        ';

        $this->assertFalse(\Scan\Kss\Parser::isKssBlock($comment));
    }
}
