<?php

/*!
 * Saying Class
 *
 * Copyright (c) 2016 Dave Olsen, http://dmolsen.com
 * Licensed under the MIT license
 *
 * Load and print sayings
 *
 */

namespace PatternLab;

use \PatternLab\Config;
use \PatternLab\Console;
use \PatternLab\Timer;

class Saying {
	
	protected $sayings;
	
	/**
	* Construct the sayings
	*/
	public function __construct() {
		
		// build the default sayings
		$sayings = array(  "have fun storming the castle",
											 "be well, do good work, and keep in touch",
											 "may the sun shine, all day long",
											 "smile :)",
											 "namaste",
											 "walk as if you are kissing the earth with your feet",
											 "to be beautiful means to be yourself",
											 "i was thinking of the immortal words of socrates, who said '...i drank what?'",
											 "let me take this moment to compliment you on your fashion sense, particularly your slippers",
											 "42",
											 "he who controls the spice controls the universe",
											 "the greatest thing you'll ever learn is just to love and be loved in return",
											 "nice wand",
											 "i don't have time for a grudge match with every poseur in a parka",
											 "han shot first",
											 "what we've got here is a failure to communicate",
											 "mama always said life was like a box of chocolates. you never know what you're gonna get",
											 "soylent green is people",
											 "a little word of advice, my friend. sometimes you gotta let those hard-to-reach chips go",
											 "you don't understand! i coulda had class. i coulda been a contender. i could've been somebody, instead of a bum, which is what i am",
											 "shop smart. shop s-mart",
											 "i see dead people",
											 "well, nobody's perfect",
											 "it's alive! it's alive!",
											 "you've got to ask yourself one question: 'do I feel lucky?' well, do ya, punk?",
											 "badges? we ain't got no badges! we don't need no badges! i don't have to show you any stinking badges!",
											 "the holy roman empire was neither holy nor roman. discuss.",
											 "well, here's another nice mess you've gotten me into!",
											 "here's johnny!",
											 "hello, gorgeous",
											 "nobody puts baby in a corner",
											 "life moves pretty fast. if you don't stop and look around once in a while, you could miss it",
											 "my precious",
											 "be yourself; everyone else is already taken",
											 "the ships hung in the sky in much the same way that bricks don't",
											 "klaatu barada nikto",
											 "i am putting myself to the fullest possible use, which is all i think that any conscious entity can ever hope to do",
											 "just what do you think you're doing, dave?",
											 "do what i do. hold tight and pretend it's a plan!",
											 "(╯°□°）╯︵ ┻━┻",
											 "¸.·´¯`·.´¯`·.¸¸.·´¯`·.¸><(((º>",
											 "@}~}~~~",
											 "(>'.')> (>'.')> (>'.')> ",
											 "\(^-^)/",
											 "you've been at this awhile; perhaps it's time for a walk outside?"
										);
		
		// grab user sayings
		$userSayings = Config::getOption("sayings");
		if (is_array($userSayings)) {
			$sayings = array_merge($sayings, $userSayings);
		}
		
		// i just didn't want to indent the crap above
		$this->sayings = $sayings;
		
	}
	
	/**
	* Randomly prints a saying after the generate is complete
	*/
	public function say() {
		
		// set a color
		$colors       = array("ok","options","info","warning");
		$randomNumber = rand(0,count($colors)-1);
		$color        = (isset($colors[$randomNumber])) ? $colors[$randomNumber] : "desc";
		
		// set a 1 in 3 chance that a saying is printed
		$randomNumber = rand(0,(count($this->sayings)-1)*3);
		if (isset($this->sayings[$randomNumber])) {
			Console::writeLine("<".$color.">".$this->sayings[$randomNumber]."...</".$color.">");
		}
		
	}
	
}
