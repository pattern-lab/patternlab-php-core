<?php

namespace Scan\Test;

use \Scan\Kss\Section;

class SectionTest extends \PHPUnit_Framework_TestCase
{
    protected static $section;

    public static function setUpBeforeClass()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

Markup: <div class="\$modifierClass"></div>

Deprecated: Styling for legacy wikis. We'll drop support for these wikis on July 13, 2007.

Experimental: An alternative signup button styling used in AB Test #195.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        self::$section = new Section($commentText);
    }

    /**
     * @test
     */
    public function getFilename()
    {
        $this->assertEmpty(self::$section->getFilename());
    }

    /**
     * @test
     */
    public function getTitle()
    {
        $expected = 'Form Button';
        $this->assertEquals($expected, self::$section->getTitle());
    }

    /**
     * @test
     */
    public function getDescription()
    {
        $expected = <<<comment
Your standard form button.

And another line describing the button.
comment;
        $this->assertEquals($expected, self::$section->getDescription());
    }

    /**
     * @test
     */
    public function getMarkup()
    {
        $expected = '<div class="$modifierClass"></div>';
        $this->assertEquals($expected, self::$section->getMarkup());
    }

    /**
     * @test
     */
    public function getMarkupNormalEmpty()
    {
        $expected = '<div class=""></div>';
        $this->assertEquals($expected, self::$section->getMarkupNormal());
    }

    /**
     * @test
     */
    public function getMarkupNormalReplacement()
    {
        $expected = '<div class="{class}"></div>';
        $this->assertEquals($expected, self::$section->getMarkupNormal('{class}'));
    }

    /**
     * @test
     */
    public function getMarkupMultiLine()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

Markup:
<div class="\$modifierClass">
    <a href="#">test</a>
</div>

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $expected = <<<comment
<div class="\$modifierClass">
    <a href="#">test</a>
</div>
comment;

        $testSection = new Section($commentText);
        $this->assertEquals($expected, $testSection->getMarkup());
    }

    public function getMarkupNull()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $testSection = new Section($commentText);
        $this->assertEmpty($testSection->getMarkup());
    }

    /**
     * @test
     */
    public function getDeprecated()
    {
        $expected = "Styling for legacy wikis. We'll drop support for these wikis on July 13, 2007.";
        $this->assertEquals($expected, self::$section->getDeprecated());
    }

    /**
     * @test
     */
    public function getDeprecatedMultiLine()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

Deprecated:
Styling for legacy wikis. We'll drop support for these wikis on
July 13, 2007.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $expected = <<<comment
Styling for legacy wikis. We'll drop support for these wikis on
July 13, 2007.
comment;

        $testSection = new Section($commentText);
        $this->assertEquals($expected, $testSection->getDeprecated());
    }

    /**
     * @test
     */
    public function getDeprecatedNull()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $testSection = new Section($commentText);
        $this->assertEmpty($testSection->getDeprecated());
    }

    /**
     * @test
     */
    public function getExperimental()
    {
        $expected = 'An alternative signup button styling used in AB Test #195.';
        $this->assertEquals($expected, self::$section->getExperimental());
    }

    /**
     * @test
     */
    public function getExperimentalMultiLine()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

Experimental:
An alternative signup button styling used in
AB Test #195.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $expected = <<<comment
An alternative signup button styling used in
AB Test #195.
comment;

        $testSection = new Section($commentText);
        $this->assertEquals($expected, $testSection->getExperimental());
    }

    /**
     * @test
     */
    public function getExperimentalNull()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

And another line describing the button.

:hover - Highlights when hovering.
:disabled - Dims the button when disabled.
.primary - Indicates button is the primary action.
.smaller - A smaller button
.altFormButton @extends .formButton - An extension of .formButton

Styleguide 2.1.1.
comment;

        $testSection = new Section($commentText);
        $this->assertEmpty($testSection->getExperimental());
    }

    /**
     * @test
     */
    public function getModifiers()
    {
        $this->assertCount(5, self::$section->getModifiers());
    }

    /**
     * @test
     */
    public function getModifiersDescriptionContainsDelimiter()
    {
        $commentText = <<<comment
# Form Button

Your standard form button.

.smaller - A smaller button - really small

Styleguide 2.1.1.
comment;

        $testSection = new Section($commentText);
        $modifiers = $testSection->getModifiers();
        $description = $modifiers[0]->getDescription();
        $expected = 'A smaller button - really small';

        $this->assertEquals($expected, $description);
    }

    /**
     * @test
     */
    public function getSection()
    {
        $this->assertEquals('2.1.1', self::$section->getSection());
    }

    /**
     * @test
     */
    public function getReference()
    {
        $this->assertEquals('2.1.1', self::$section->getReference());

        $section = new Section('// Styleguide 3.0.0');
        $this->assertEquals('3.0.0', $section->getReference());
    }

    /**
     * @test
     */
    public function getReferenceTrimmed()
    {
        $this->assertEquals('2.1.1', self::$section->getReference(true));

        $section = new Section('// Styleguide 3.0.0');
        $this->assertEquals('3', $section->getReference(true));
    }

    /**
     * @test
     */
    public function trimReference()
    {
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.0'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.0.'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.00000000'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.00000000.'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.0.0.0.0'));
        $this->assertEquals('1.1.1', Section::trimReference('1.1.1.0.00.000.0000'));
        $this->assertEquals('1.0.1.1.0.00.1.0.10000.10', Section::trimReference('1.0.1.1.0.00.1.0.10000.10'));
        $this->assertEquals('1.0.1.1.0.00.1.0.10000.10', Section::trimReference('1.0.1.1.0.00.1.0.10000.10.00'));
    }

    /**
     * @test
     */
    public function belongsToReference()
    {
        $this->assertTrue(self::$section->belongsToReference('2'));
        $this->assertTrue(self::$section->belongsToReference('2.1'));
        $this->assertTrue(self::$section->belongsToReference('2.1.1'));
        $this->assertTrue(self::$section->belongsToReference('2.1.1.0'));
        $this->assertTrue(self::$section->belongsToReference('2.1.1.0.0'));
        $this->assertTrue(self::$section->belongsToReference('2.1.1.0.0.'));

        $this->assertFalse(self::$section->belongsToReference('2.1.1.1'));
        $this->assertFalse(self::$section->belongsToReference('2.1.2'));
        $this->assertFalse(self::$section->belongsToReference('2.2.1'));
        $this->assertFalse(self::$section->belongsToReference('3'));
        $this->assertFalse(self::$section->belongsToReference('1.1'));

        $commentText = <<<comment
# Section test

Styleguide 20.
comment;

        $section20 = new Section($commentText);

        $this->assertFalse($section20->belongsToReference('2'));
        $this->assertTrue($section20->belongsToReference('20'));
        $this->assertFalse($section20->belongsToReference('200'));
    }

    /**
     * @test
     */
    public function getDepth()
    {
        $this->assertEquals(2, self::$section->getDepth());
    }

    /**
     * @test
     */
    public function calcDepth()
    {
        $this->assertEquals(0, Section::calcDepth('1'));
        $this->assertEquals(0, Section::calcDepth('1.0.0'));
        $this->assertEquals(1, Section::calcDepth('1.1'));
        $this->assertEquals(1, Section::calcDepth('1.1.0'));
        $this->assertEquals(2, Section::calcDepth('1.1.1'));
        $this->assertEquals(3, Section::calcDepth('1.1.1.1'));
        $this->assertEquals(3, Section::calcDepth('1.1.0.1'));
    }

    /**
     * @test
     */
    public function getDepthScore()
    {
        $this->assertEquals(2.11, self::$section->getDepthScore());
    }

    /**
     * @test
     */
    public function calcDepthScore()
    {
        $this->assertEquals(1, Section::calcDepthScore('1'));
        $this->assertEquals(1, Section::calcDepthScore('1.0.0'));
        $this->assertEquals(1.1, Section::calcDepthScore('1.1'));
        $this->assertEquals(1.1, Section::calcDepthScore('1.1.0'));
        $this->assertEquals(1.11, Section::calcDepthScore('1.1.1'));
        $this->assertEquals(1.111, Section::calcDepthScore('1.1.1.1'));
        $this->assertEquals(1.101, Section::calcDepthScore('1.1.0.1'));
    }

    /**
     * @test
     */
    public function depthSort()
    {
        $sections = array(
            '2' => new Section('// Styleguide 2'),
            '3.2.1' => new Section('// Styleguide 3.2.1'),
            '3.1' => new Section('// Styleguide 3.1'),
            '1.2' => new Section('// Styleguide 1.2'),
            '1' => new Section('// Styleguide 1'),
            '3.0.0' => new Section('// Styleguide 3.0.0'),
            '2.1.2' => new Section('// Styleguide 2.1.2'),
        );

        uasort($sections, '\Scan\Kss\Section::depthSort');

        $keys = array_keys($sections);
        $expectedKeys = array(
            '1',
            '2',
            '3.0.0',
            '1.2',
            '3.1',
            '2.1.2',
            '3.2.1'
        );
        $this->assertEquals($expectedKeys, $keys);
    }

    /**
     * @test
     */
    public function depthScoreSort()
    {
        $sections = array(
            '2' => new Section('// Styleguide 2'),
            '3.2.1' => new Section('// Styleguide 3.2.1'),
            '3.1' => new Section('// Styleguide 3.1'),
            '1.2' => new Section('// Styleguide 1.2'),
            '1' => new Section('// Styleguide 1'),
            '3.0.0' => new Section('// Styleguide 3.0.0'),
            '2.1.2' => new Section('// Styleguide 2.1.2'),
        );

        uasort($sections, '\Scan\Kss\Section::depthScoreSort');

        $keys = array_keys($sections);
        $expectedKeys = array(
            '1',
            '1.2',
            '2',
            '2.1.2',
            '3.0.0',
            '3.1',
            '3.2.1'
        );
        $this->assertEquals($expectedKeys, $keys);
    }
}
