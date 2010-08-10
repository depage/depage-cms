<?php
/**
 * CssMin - A (simple) css minifier with benefits
 * 
 * --
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * --
 *
 * @package		CssMin
 * @author		Joe Scylla <joe.scylla@gmail.com>
 * @copyright	2008 - 2010 Joe Scylla <joe.scylla@gmail.com>
 * @license		http://opensource.org/licenses/mit-license.php MIT License
 * @version		2.0.1.b1 (2010-08-10)
 */

class CssMin
	{
	/**
	 * State: Is in document
	 * 
	 * @var integer
	 */
	const T_DOCUMENT = 1;
	/**
	 * Token: Comment
	 * 
	 * @var integer
	 */
	const T_COMMENT = 2;
	/**
	 * Token: Generic at-rule
	 * 
	 * @var integer
	 */
	const T_AT_RULE = 3;
	/**
	 * Token: Start of @media block
	 * 
	 * @var integer
	 */
	const T_AT_MEDIA_START = 4;
	/**
	 * State: Is in @media block
	 * 
	 * @var integer
	 */
	const T_AT_MEDIA = 5;
	/**
	 * Token: End of @media block
	 * 
	 * @var integer
	 */
	const T_AT_MEDIA_END = 6;
	/**
	 * Token: Start of @font-face block
	 * 
	 * @var integer
	 */
	const T_AT_FONT_FACE_START = 7;
	/**
	 * State: Is in @font-face block
	 * 
	 * @var integer
	 */
	const T_AT_FONT_FACE = 8;
	/**
	 * Token: @font-face declaration
	 * 
	 * @var integer
	 */
	const T_FONT_FACE_DECLARATION = 9;
	/**
	 * Token: End of @font-face block
	 * 
	 * @var integer
	 */
	const T_AT_FONT_FACE_END = 10;
	/**
	 * Token: Start of @page block
	 * 
	 * @var integer
	 */
	const T_AT_PAGE_START = 11;
	/**
	 * State: Is in @page block
	 * 
	 * @var integer
	 */
	const T_AT_PAGE = 12;
	/**
	 * Token: @page declaration
	 * 
	 * @var integer
	 */
	const T_PAGE_DECLARATION = 13;
	/**
	 * Token: End of @page block
	 * 
	 * @var integer
	 */
	const T_AT_PAGE_END = 14;
	/**
	 * Token: Start of ruleset
	 * 
	 * @var integer
	 */
	const T_RULESET_START = 15;
	/**
	 * Token: Ruleset selectors
	 * 
	 * @var integer
	 */
	const T_SELECTORS = 16;
	/**
	 * Token: Start of declarations
	 * 
	 * @var integer
	 */
	const T_DECLARATIONS_START = 17;
	/**
	 * State: Is in declarations
	 * 
	 * @var integer
	 */
	const T_DECLARATIONS = 18;
	/**
	 * Token: Declaration
	 * 
	 * @var integer
	 */
	const T_DECLARATION = 19;
	/**
	 * Token: End of declarations
	 * 
	 * @var integer
	 */
	const T_DECLARATIONS_END = 20;
	/**
	 * Token: End of ruleset
	 * 
	 * @var integer
	 */
	const T_RULESET_END = 21;
	/**
	 * Token: Start of @variables block
	 * 
	 * @var integer
	 */
	const T_AT_VARIABLES_START = 100;
	/**
	 * State: Is in @variables block
	 * 
	 * @var integer
	 */
	const T_AT_VARIABLES = 101;
	/**
	 * Token: @variables declaration
	 * 
	 * @var integer
	 */
	const T_VARIABLE_DECLARATION = 102;
	/**
	 * Token: End of @variables block
	 * 
	 * @var integer
	 */
	const T_AT_VARIABLES_END = 103;
	/**
	 * State: Is in string
	 * 
	 * @var integer
	 */
	const T_STRING = 255;
	/**
	 * Minifies the Css.
	 * 
	 * @param string $css
	 * @param array $config [optional]
	 * @return string
	 */
	public static function minify($css, $config = array())
		{
		$tokens = self::parse($css);
		$config = array_merge(array
			(
			"remove-tokens"					=> array(self::T_COMMENT),
			"remove-empty-blocks"			=> true,
			"remove-empty-rulesets"			=> true,
			"remove-last-ruleset-semicolon"	=> true,
			"convert-css3-properties"		=> true,
			"convert-color-values"			=> false,
			"compress-color-values"			=> false,
			"compress-unit-values"			=> false,
			"emulate-css3-variables"		=> true,
			"css3-translation"				=> array
				(
				"border-radius"					=> array("-moz-border-radius", "-webkit-border-radius", "-khtml-border-radius"),
				"border-top-left-radius"		=> array("-moz-border-radius-topleft", "-webkit-border-top-left-radius", "-khtml-top-left-radius"),
				"border-top-right-radius"		=> array("-moz-border-radius-topright", "-webkit-border-top-right-radius", "-khtml-top-right-radius"),
				"border-bottom-right-radius"	=> array("-moz-border-radius-bottomright", "-webkit-border-bottom-right-radius", "-khtml-border-bottom-right-radius"),
				"border-bottom-left-radius"		=> array("-moz-border-radius-bottomleft", "-webkit-border-bottom-left-radius", "-khtml-border-bottom-left-radius"),
				"box-shadow"					=> array("-moz-box-shadow", "-webkit-box-shadow", "-khtml-box-shadow"),
				"opacity"						=> array("-moz-opacity", "-webkit-opacity", "-khtml-opacity"),
				"text-shadow"					=> array("-moz-text-shadow", "-webkit-text-shadow", "-khtml-text-shadow")
				)
			), $config);
		// Remove tokens
		if (!$config["emulate-css3-variables"])
			{
			$config["remove-tokens"] = array_merge($config["remove-tokens"], array(self::T_AT_VARIABLES_START, self::T_VARIABLE_DECLARATION, self::T_AT_VARIABLES_END));
			}
		for($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			if (in_array($tokens[$i][0], $config["remove-tokens"]))
				{
				unset($tokens[$i]);
				}
			}
		$tokens = array_values($tokens);
		// Emulate css3 variables
		if ($config["emulate-css3-variables"])
			{
			// Parse variables
			$variables = array();
			for($i = 0, $l = count($tokens); $i < $l; $i++)
				{
				if ($tokens[$i][0] == self::T_VARIABLE_DECLARATION)
					{
					for($i2 = 0, $l2 = count($tokens[$i][3]); $i2 < $l2; $i2++)
						{
						if (!isset($variables[$tokens[$i][3][$i2]]))
							{
							$variables[$tokens[$i][3][$i2]] = array();
							}
						$variables[$tokens[$i][3][$i2]][$tokens[$i][1]] = $tokens[$i][2];
						}
					}
				}
			// Parse declarations for variable statements
			for($i = 0, $l = count($tokens); $i < $l; $i++)
				{
				if ($tokens[$i][0] == self::T_DECLARATION)
					{
					if (substr($tokens[$i][2], 0, 4) == "var(" && substr($tokens[$i][2], -1, 1) == ")")
						{
						$tokens[$i][3][] = "all";
						$variable = trim(substr($tokens[$i][2], 4, -1));
						for($i2 = 0, $l2 = count($tokens[$i][3]); $i2 < $l2; $i2++)
							{
							if (isset($variables[$tokens[$i][3][$i2]][$variable]))
								{
								$tokens[$i][2] = $variables[$tokens[$i][3][$i2]][$variable];
								break;
								}
							}
						}
					}
				}
			}
		// Remove empty rulesets
		if ($config["remove-empty-rulesets"])
			{
			for($i = 0, $l = count($tokens); $i < $l; $i++)
				{
				// Remove empty rulesets
				if ($tokens[$i][0] == self::T_RULESET_START && $tokens[$i+4][0] == self::T_RULESET_END)
					{
					unset($tokens[$i]); 	// T_RULESET_START
					unset($tokens[++$i]);	// T_SELECTORS
					unset($tokens[++$i]);	// T_DECLARATIONS_START
					unset($tokens[++$i]);	// T_DECLARATIONS_END
					unset($tokens[++$i]);	// T_RULESET_END
					}
				}
			}
		$tokens = array_values($tokens);
		// Compression and conversion
		for($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			// Remove empty @media, @font-face or @page blocks
			if ($config["remove-empty-blocks"] && (
				($tokens[$i][0] == self::T_AT_MEDIA_START && $tokens[$i+1][0] == self::T_AT_MEDIA_END)
				|| ($tokens[$i][0] == self::T_AT_FONT_FACE_START && $tokens[$i+1][0] == self::T_AT_FONT_FACE_END)
				|| ($tokens[$i][0] == self::T_AT_PAGE_START && $tokens[$i+1][0] == self::T_AT_PAGE_END)
				))
				{
				unset($tokens[$i]);		// T_AT_MEDIA_START, T_AT_FONT_FACE_START, T_AT_PAGE_START
				unset($tokens[++$i]);	// T_AT_MEDIA_END, T_AT_FONT_FACE_END, T_AT_PAGE_END
				}
			// Compress unit values
			if ($config["compress-unit-values"] && $tokens[$i][0] == self::T_DECLARATION)
				{
				// Compress "0px" to "0"
				$tokens[$i][2] = preg_replace("/(^| )0(%|em|ex|px|in|cm|mm|pt|pc)/i", "\${1}0", $tokens[$i][2]);
				// Compress "0.5px" to ".5px"
				$tokens[$i][2] = preg_replace("/(^| )0\.([0-9]{1})(%|em|ex|px|in|cm|mm|pt|pc)/i", "\${1}.\${2}\${3}", $tokens[$i][2]);
				// Compress "0 0 0 0" to "0"
				if ($tokens[$i][2] == "0 0 0 0") {$tokens[$i][2] = "0";}
				}
			// Convert color values
			if ($config["convert-color-values"] && $tokens[$i][0] == self::T_DECLARATION)
				{
				// Convert RGB color values to hex ("rgb(200,60%,5)" => "#c89905")
				preg_match("/rgb\s*\(\s*([0-9%]+)\s*,\s*([0-9%]+)\s*,\s*([0-9%]+)\s*\)/i", $tokens[$i][2], $m);
				if ($m)
					{
					for ($i2 = 1, $l2 = count($m); $i2 < $l2; $i2++)
						{
						if (strpos("%", $m[$i2]) !== false)
							{
							$m[$i2] = substr($m[$i2], 0, -1);
							$m[$i2] = (int) (256 * ($m[$i2] / 100));
							}
						$m[$i2] = str_pad(dechex($m[$i2]),  2, "0", STR_PAD_LEFT);
						}
					$tokens[$i][2] = str_replace($m[0], "#" . $m[1] . $m[2] . $m[3], $tokens[$i][2]);
					}
				}
			// Compress color values ("#aabbcc" to "#abc") 
			if ($config["compress-color-values"] && $tokens[$i][0] == self::T_DECLARATION)
				{
				preg_match("/\#([0-9a-f]{6})/i", $tokens[$i][2], $m);
				if ($m)
					{
					if (substr($m[1], 1, 1) == substr($m[1], 2, 1) && substr($m[1], 3, 1) == substr($m[1], 4, 1) && substr($m[1], 5, 1) == substr($m[1], 6, 1))
						{
						$tokens[$i][2] = str_replace($m[0], "#" . substr($m[1], 1, 1) . substr($m[1],3,1) . substr($m[1],5,1), $tokens[$i][2]);
						}
					}
				}
			}
		$tokens = array_values($tokens);
		// Create minified css
		$r = "";
		for($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			// T_AT_RULE
			if ($tokens[$i][0] == self::T_AT_RULE)
				{
				$r .= "@" . $tokens[$i][1] . " " . $tokens[$i][2] . ";";
				}
			// T_AT_MEDIA_START
			elseif ($tokens[$i][0] == self::T_AT_MEDIA_START)
				{
				if (count($tokens[$i][1]) == 1 && $tokens[$i][1][0] == "all")
					{
					$r .= "@media{";
					}
				else
					{
					$r .= "@media " . implode(",", $tokens[$i][1]) . "{";
					}
				}
			// T_AT_FONT_FACE_START
			elseif ($tokens[$i][0] == self::T_AT_FONT_FACE_START)
				{
				$r .= "@font-face{";
				}
			// T_FONT_FACE_DECLARATION
			elseif ($tokens[$i][0] == self::T_FONT_FACE_DECLARATION)
				{
				$r .= $tokens[$i][1] . ":" . $tokens[$i][2] . ($config["remove-last-ruleset-semicolon"] && $tokens[$i + 1][0] == self::T_AT_FONT_FACE_END ? "" : ";");
				}
			// T_AT_PAGE_START
			elseif ($tokens[$i][0] == self::T_AT_PAGE_START)
				{
				$r .= "@page{";
				}
			// T_PAGE_DECLARATION
			elseif ($tokens[$i][0] == self::T_PAGE_DECLARATION)
				{
				$r .= $tokens[$i][1] . ":" . $tokens[$i][2] . ($config["remove-last-ruleset-semicolon"] && $tokens[$i + 1][0] == self::T_AT_PAGE_END ? "" : ";");
				}
			// T_SELECTORS
			elseif ($tokens[$i][0] == self::T_SELECTORS)
				{
				$r .= implode(",", $tokens[$i][1]);
				}
			// Start of declarations
			elseif ($tokens[$i][0] == self::T_DECLARATIONS_START)
				{
				$r .=  "{";
				}
			// T_DECLARATION
			elseif ($tokens[$i][0] == self::T_DECLARATION)
				{
				if (isset($config["css3-translation"][$tokens[$i][1]]))
					{
					foreach ($config["css3-translation"][$tokens[$i][1]] as $css3Declaration)
						{
						$r .= $css3Declaration . ":" . $tokens[$i][2] . ";";
						}
					}
				$r .= $tokens[$i][1] . ":" . $tokens[$i][2] . ($config["remove-last-ruleset-semicolon"] && $tokens[$i + 1][0] == self::T_DECLARATIONS_END ? "" : ";");
				}
			// T_DECLARATIONS_END, T_AT_MEDIA_END, T_AT_FONT_FACE_END, T_AT_PAGE_END
			elseif (in_array($tokens[$i][0], array(self::T_DECLARATIONS_END, self::T_AT_MEDIA_END, self::T_AT_FONT_FACE_END, self::T_AT_PAGE_END)))
				{
				$r .= "}";
				}
			else
				{
				// Tokens with no output:
				// T_COMMENT
				// T_RULESET_START
				// T_RULESET_END
				// T_AT_VARIABLES_START
				// T_VARIABLE_DECLARATION
				// T_AT_VARIABLES_END
				}
			}
		return $r;
		}
	/**
	 * Parses the Css and returns a array of tokens.
	 * 
	 * @param string $css
	 * @return array
	 */
	public static function parse($css)
		{
		// Settings
		$sDefaultTrim		= " \t\n\r\0\x0B";
		$sDefaultScope		= array("all");
		$sTokenChars		= "@{};:\n\"'/*,";
		
		// Basic variables
		$c					= null;
		$buffer 			= "";
		$state				= array(self::T_DOCUMENT);
		$currentState		= self::T_DOCUMENT;
		$scope				= $sDefaultScope;
		$selectors			= array();
		$stringChar			= null;
		$filterWs			= true;
		
		// Return value
		$r 					= array();
		// 
		for ($i = 0, $l = strlen($css); $i < $l; $i++)
			{
			$c = substr($css, $i, 1);
			// Filter out whitespace chars
			if ($filterWs && ($c == "\t" || ($c == " " && $c == $p)))
				{
				continue;
				}
			$buffer .= $c;
			if (strpos($sTokenChars, $c) !== false)
				{
				$currentState	= $state[count($state) - 1];
				/*
				 * Start of comment
				 */
				if ($currentState != self::T_STRING && substr($css, $i, 2) == "/*")
					{
					$buffer 	= $c;
					$filterWs	= false;
					array_push($state, self::T_COMMENT);
					}
				/*
				 * End of comment
				 */
				elseif ($currentState != self::T_STRING && $currentState == self::T_COMMENT && substr($css, $i, 2) == "*/")
					{
					$buffer		.= substr($css, $i, 2);
					$r[]		= array(self::T_COMMENT, trim($buffer));
					$i			= $i + 2;
					$buffer		= "";
					$filterWs	= true;
					array_pop($state);
					}
				/*
				 * Start of at-rule @media block
				 */
				elseif ($currentState == self::T_DOCUMENT && $c == "@" && strtolower(substr($css, $i, 6)) == "@media")
					{
					$i			= $i + 6;
					$buffer 	= "";
					array_push($state, self::T_AT_MEDIA_START);
					}
				/*
				 * At-rule @media block media types
				 */
				elseif ($currentState == self::T_AT_MEDIA_START && $c == "{")
					{
					$buffer 	= strtolower(trim($buffer, $sDefaultTrim . "{"));
					$scope		= $buffer != "" ? array_filter(array_map("trim", explode(",", $buffer))) : $sDefaultScope;
					$r[]		= array(self::T_AT_MEDIA_START, $scope);
					$i			= $i++;
					$buffer		= "";
					array_pop($state);
					array_push($state, self::T_AT_MEDIA);
					}
				/*
				 * End of at-rule @media block
				 */
				elseif ($currentState == self::T_AT_MEDIA && $c == "}")
					{
					$r[]		= array(self::T_AT_MEDIA_END);
					$scope		= $sDefaultScope;
					$buffer		= "";
					array_pop($state);
					}
				/*
				 * Start of at-rule @font-face block
				 */
				elseif ($currentState == self::T_DOCUMENT && $c == "@" && strtolower(substr($css, $i, 10)) == "@font-face")
					{
					$r[]		= array(self::T_AT_FONT_FACE_START);
					$i			= $i + 10;
					$buffer 	= "";
					array_push($state, self::T_AT_FONT_FACE);
					}
				/*
				 * @font-face declaration: Property
				 */
				elseif ($c == ":" && $currentState == self::T_AT_FONT_FACE)
					{
					$property	= trim($buffer, $sDefaultTrim . ":{");
					$buffer		= "";
					array_push($state, self::T_FONT_FACE_DECLARATION);
					}
				/*
				 * @font-face declaration: Value
				 */
				elseif ($currentState == self::T_FONT_FACE_DECLARATION && strpos(";}\n", $c) !== false)
					{
					$value		= trim($buffer, $sDefaultTrim . ";}");
					$r[]		= array(self::T_FONT_FACE_DECLARATION, $property, $value, $scope);
					$buffer		= "";
					array_pop($state);
					// font face declaration closed with a right curly brace => closes @font-face block
					if ($c == "}")
						{
						array_pop($state);
						$r[]		= array(self::T_AT_FONT_FACE_END);
						}
					}
				/*
				 * End of at-rule @font-face block
				 */
				elseif ($currentState == self::T_AT_FONT_FACE && $c == "}")
					{
					$r[]		= array(self::T_AT_FONT_FACE_END);
					$buffer		= "";
					array_pop($state);
					}
				/*
				 * Start of at-rule @page block
				 */
				elseif ($currentState == self::T_DOCUMENT && $c == "@" && strtolower(substr($css, $i, 5)) == "@page")
					{
					$r[]		= array(self::T_AT_PAGE_START);
					$i			= $i + 5;
					$buffer 	= "";
					array_push($state, self::T_AT_PAGE);
					}
				/*
				 * @page declaration: Property
				 */
				elseif ($c == ":" && $currentState == self::T_AT_PAGE)
					{
					$property	= trim($buffer, $sDefaultTrim . ":{");
					$buffer		= "";
					array_push($state, self::T_PAGE_DECLARATION);
					}
				/*
				 * @page declaration: Value
				 */
				elseif ($currentState == self::T_PAGE_DECLARATION && strpos(";}\n", $c) !== false)
					{
					$value		= trim($buffer, $sDefaultTrim . ";}");
					$r[]		= array(self::T_PAGE_DECLARATION, $property, $value, $scope);
					$buffer		= "";
					array_pop($state);
					// font face declaration closed with a right curly brace => closes @font-face block
					if ($c == "}")
						{
						array_pop($state);
						$r[]		= array(self::T_AT_PAGE_END);
						}
					}
				/*
				 * End of at-rule @page block
				 */
				elseif ($currentState == self::T_AT_PAGE && $c == "}")
					{
					$r[]		= array(self::T_AT_PAGE_END);
					$buffer		= "";
					array_pop($state);
					}
				/*
				 * Start of at-rule @variables block
				 */
				elseif ($currentState == self::T_DOCUMENT && $c == "@" && strtolower(substr($css, $i, 10)) == "@variables")
					{
					$i			= $i + 10;
					$buffer 	= "";
					array_push($state, self::T_AT_VARIABLES_START);
					}
				/*
				 * @variables media types
				 */
				elseif ($c == "{" && $currentState == self::T_AT_VARIABLES_START)
					{
					$buffer 	= strtolower(trim($buffer, $sDefaultTrim . "{"));
					$r[]		= array(self::T_AT_VARIABLES_START, $scope);
					$scope		= $buffer != "" ? array_filter(array_map("trim", explode(",", $buffer))) : $sDefaultScope;
					$i			= $i++;
					$buffer		= "";
					array_pop($state);
					array_push($state, self::T_AT_VARIABLES);
					}
				/*
				 * @variables declaration: Property
				 */
				elseif ($c == ":" && $currentState == self::T_AT_VARIABLES)
					{
					$property	= trim($buffer, $sDefaultTrim . ":");
					$buffer		= "";
					array_push($state, self::T_VARIABLE_DECLARATION);
					}
				/*
				 * @variables declaration: Value
				 */
				elseif ($currentState == self::T_VARIABLE_DECLARATION && strpos(";}\n", $c) !== false)
					{
					$value		= trim($buffer, $sDefaultTrim . ";}");
					$r[]		= array(self::T_VARIABLE_DECLARATION, $property, $value, $scope);
					$buffer		= "";
					array_pop($state);
					// variable declaration closed with a right curly brace => closes @variables block
					if ($c == "}")
						{
						array_pop($state);
						$r[]		= array(self::T_AT_VARIABLES_END);
						$scope		= $sDefaultScope;
						}
					}
				/*
				 * End of at-rule @variables block
				 */
				elseif ($currentState == self::T_AT_VARIABLES && $c == "}")
					{
					$r[]		= array(self::T_AT_VARIABLES_END);
					$scope		= $sDefaultScope;
					$buffer		= "";
					array_pop($state);
					}
				/*
				 * Start of document level at-rule
				 */
				elseif ($currentState == self::T_DOCUMENT && $c == "@")
					{
					$buffer		= "";
					array_push($state, self::T_AT_RULE);
					}
				
				/*
				 * End of document level at-rule
				 */
				elseif ($currentState == self::T_AT_RULE && $c == ";")
					{
					$pos		= strpos($buffer, " ");
					$rule		= substr($buffer, 0, $pos);
					$value		= trim(substr($buffer, $pos), $sDefaultTrim . ";");
					$r[]		= array(self::T_AT_RULE, $rule, $value);
					$buffer		= "";
					array_pop($state);
					}
				/**
				 * Selector
				 */
				elseif (($currentState == self::T_AT_MEDIA || $currentState ==  self::T_DOCUMENT) && $c == ",")
					{
					$selectors[]= trim($buffer, $sDefaultTrim . ",");
					$buffer		= "";
					}
				/*
				 * Start of ruleset
				 */
				elseif (($currentState == self::T_AT_MEDIA || $currentState == self::T_DOCUMENT) && $c == "{")
					{
					$selectors[]= trim($buffer, $sDefaultTrim . "{");
					$selectors 	= array_filter(array_map("trim", $selectors));
					$r[]		= array(self::T_RULESET_START);
					$r[]		= array(self::T_SELECTORS, $selectors);
					$r[]		= array(self::T_DECLARATIONS_START);
					$buffer		= "";
					$selectors	= array();
					array_push($state, self::T_DECLARATIONS);
					}
				/*
				 * Declaration: Property
				 */
				elseif ($currentState == self::T_DECLARATIONS && $c == ":")
					{
					$property	= trim($buffer, $sDefaultTrim . ":;");
					$buffer		= "";
					array_push($state, self::T_DECLARATION);
					}
				/*
				 * Declaration: Value
				 */
				elseif ($currentState == self::T_DECLARATION && strpos(";}\n", $c) !== false)
					{
					$value		= trim($buffer, $sDefaultTrim . ";}");
					$r[]		= array(self::T_DECLARATION, $property, $value, $scope);
					$buffer		= "";
					array_pop($state);
					// declaration closed with a right curly brace => close ruleset
					if ($c == "}")
						{
						array_pop($state);
						$r[]		= array(self::T_DECLARATIONS_END);
						$r[]		= array(self::T_RULESET_END);
						}
					}
				/*
				 * End of ruleset
				 */
				elseif ($currentState == self::T_DECLARATIONS && $c == "}")
					{
					$r[]		= array(self::T_DECLARATIONS_END);
					$r[]		= array(self::T_RULESET_END);
					$buffer		= "";
					array_pop($state);
					}
				/*
				 * Start of string
				 */
				elseif ($currentState != self::T_STRING && ($c == "\"" || $c == "'"))
					{
					$stringChar	= $c;
					$filterWs	= false;
					array_push($state, self::T_STRING);
					}
				/*
				 * End of string
				 */
				elseif ($currentState == self::T_STRING && $c === $stringChar && (substr($css, $i - 1, 1) != "\\" || substr($css, $i - 2, 2) == "\\\\"))
					{
					$filterWs	= true;
					array_pop($state);
					$stringChar = null;
					}
				}
			$p = $c;
			}
		return $r;
		}
	}
?>