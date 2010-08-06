<?php
/**
 * CssMin - A simple CSS minifier.
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
 * @version		2.0.0.b1 (2010-08-06)
 */

class CssMin
	{
	/**
	 * Comment token
	 * 
	 * @var integer
	 */
	const T_COMMENT = 1;
	/**
	 * At-rule token
	 * 
	 * @var integer
	 */
	const T_AT_RULE = 2;
	/**
	 * Start of @media-block token
	 * 
	 * @var integer
	 */
	const T_AT_RULE_MEDIA_START = 3;
	/**
	 * Start of ruleset token
	 * 
	 * @var integer
	 */
	const T_RULESET_START = 4;
	/**
	 * Selector token
	 * 
	 * @var integer
	 */
	const T_SELECTORS = 5;
	/**
	 * Start of declarations token
	 * 
	 * @var integer
	 */
	const T_DECLARATIONS_START = 6;
	/**
	 * Declaration token
	 * 
	 * @var integer
	 */
	const T_DECLARATION = 7;
	/**
	 * End of declarations token
	 * 
	 * @var integer
	 */
	const T_DECLARATIONS_END = 8;
	/**
	 * End of ruleset token
	 * 
	 * @var integer
	 */
	const T_RULESET_END = 9;
	/**
	 * End of @media-block token
	 * 
	 * @var integer
	 */
	const T_AT_RULE_MEDIA_END = 10;
	/**
	 * 
	 * @var integer
	 */
	const T_CSS3_VARIABLES_START = 20;
	/**
	 * 
	 * @var integer
	 */
	const T_CSS3_VARIABLE_DECLARATION = 21;
	/**
	 * 
	 * @var integer
	 */
	const T_CSS3_VARIABLES_END = 22;
	/**
	 * 
	 * @param string $css
	 * @param string $configure
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
			$config["remove-tokens"] = array_merge($config["remove-tokens"], array(self::T_CSS3_VARIABLES_START, self::T_CSS3_VARIABLE_DECLARATION, self::T_CSS3_VARIABLES_END));
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
				if ($tokens[$i][0] == self::T_CSS3_VARIABLE_DECLARATION)
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
								}
							}
						}
					}
				}
			}
		
		// Compression
		for($i = 0, $l = count($tokens); $i < $l; $i++)
			{
			// Remove empty blocks
			if ($config["remove-empty-blocks"] && $tokens[$i][0] == self::T_AT_RULE_MEDIA_START && $tokens[$i+1][0] == self::T_AT_RULE_MEDIA_END)
				{
				unset($tokens[$i]);		// T_AT_RULE_MEDIA_START
				unset($tokens[++$i]);	// T_AT_RULE_MEDIA_END
				}
			// Remove empty rulesets
			if ($config["remove-empty-rulesets"] && $tokens[$i][0] == self::T_RULESET_START && $tokens[$i+4][0] == self::T_RULESET_END)
				{
				unset($tokens[$i]); 	// T_RULESET_START
				unset($tokens[++$i]);	// T_SELECTORS
				unset($tokens[++$i]);	// T_DECLARATIONS_START
				unset($tokens[++$i]);	// T_DECLARATIONS_END
				unset($tokens[++$i]);	// T_RULESET_END
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
			if ($config["compress-color-values"] && $tokens[$i][0] == CssParser::T_DECLARATION)
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
			// Comment
			if ($tokens[$i][0] == self::T_COMMENT)
				{
				// Token has no outout
				}
			// At-Rule
			elseif ($tokens[$i][0] == self::T_AT_RULE)
				{
				$r .= "@" . $tokens[$i][1] . " " . $tokens[$i][2] . ";";
				}
			// Start of Media block
			elseif ($tokens[$i][0] == self::T_AT_RULE_MEDIA_START)
				{
				$r .= "@" . $tokens[$i][1] . " " . implode(",", $tokens[$i][2]) . "{";
				}
			// Start of ruleset
			elseif ($tokens[$i][0] == self::T_RULESET_START)
				{
				// Token has no outout
				}
			// Selectors
			elseif ($tokens[$i][0] == self::T_SELECTORS)
				{
				$r .= implode(",", $tokens[$i][1]);
				}
			// Start of declarations
			elseif ($tokens[$i][0] == self::T_DECLARATIONS_START)
				{
				$r .=  "{";
				}
			// Declaration
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
			// End of declarations
			elseif ($tokens[$i][0] == self::T_DECLARATIONS_END)
				{
				$r .= "}";
				}
			// End of ruleset
			elseif ($tokens[$i][0] == self::T_RULESET_END)
				{
				// Token has no outout
				}
			// End of media block
			elseif ($tokens[$i][0] == self::T_AT_RULE_MEDIA_END)
				{
				$r .= "}";
				}
			// Start of variables block
			elseif ($tokens[$i][0] == self::T_CSS3_VARIABLES_START)
				{
				// Token has no outout
				}
			// Variable declaration
			elseif ($tokens[$i][0] == self::T_CSS3_VARIABLE_DECLARATION)
				{
				// Token has no outout
				}
			// Edn of variables block
			elseif ($tokens[$i][0] == self::T_CSS3_VARIABLES_END)
				{
				// Token has no outout
				}
			}
		return $r;
		}
	/**
	 * 
	 * @param string $css
	 * @return array
	 */
	public static function parse($css)
		{
		// Settings
		$sDefaultTrim		= " \t\n\r\0\x0B";
		$sDefaultScope		= array("all");
		$sQuoteChars		= array("\"", "'");
		$sVariableDeclEol	= array(";", "}");
		$sDeclEol 			= array(";", "}", "\n");
		$sDeclPropertyTrim	= $sDefaultTrim . ":";
		$sDeclValueTrim		= $sDefaultTrim . ";}";
		$sSelValueTrim		= $sDefaultTrim . "{";
		$sAtRuleTrim		= $sDefaultTrim . ";";
		$sAtRuleEol			= array(";", "\n");
		// Basic variables
		$css				= " " . $css;
		$l					= strlen($css);
		$i					= 0;
		$buffer 			= "";
		$varScope 			= $sDefaultScope;
		$mediaScope			= $sDefaultScope;
		$inString			= false;
		$inMedia			= true;
		$quoteChar			= null;
		// Return value
		$r 					= array();
		// 
		for (;$i < $l; $i++)
			{
			$buffer .= $c = substr($css, $i, 1);
			// Comment (document- and media block level)
			if (substr($css, $i, 2) == "/*")
				{
				for ($i++; $i < $l; $i++)
					{
					if (substr($css, $i, 2) == "*/") {break;}
					$buffer .= substr($css, $i, 1);
					}
				$comment	= trim($buffer . substr($css, $i, 2));
				$r[]		= array(self::T_COMMENT, $comment);
				$i			= $i + 2;
				$buffer 	= "";
				}
			// Variables block
			elseif ($c == "@" && strtolower(substr($css, $i, 10)) == "@variables")
				{
				$buffer = "";
				for ($i = $i + 10 ;$i < $l; $i++)
					{
					if (substr($css, $i, 1) == "{") {break;}
					$buffer .= substr($css, $i, 1);
					}
				$buffer 	= trim($buffer);
				$value		= $buffer != "" ? explode(",", $buffer) : $sDefaultScope;
				$r[] 		= array(self::T_CSS3_VARIABLES_START, $value);
				$varScope	= $value;
				$buffer 	= "";
				for ($i++ ;$i < $l; $i++)
					{
					// Comment (variables block level)
					if (substr($css, $i, 2) == "/*")
						{
						for (;$i < $l; $i++)
							{
							if (substr($css, $i, 2) == "*/") {break;}
							$buffer .= substr($css, $i, 1);
							}
						$comment	= trim($buffer . substr($css, $i, 2));
						$r[]		= array(self::T_COMMENT, $comment);
						$i			= $i + 2;
						$buffer 	= "";
						}
					// Variable declaration
					elseif ($c == ":")
						{
						$variable	= trim(substr($buffer, 0, -1));
						$buffer		= "";
						for (;$i < $l; $i++)
							{
							if (!$inString && in_array($c, $sQuoteChars))
								{
								$inString	= true;
								$quoteChar	= $c;
								}
							elseif ($inString && $c === $quoteChar && (substr($css, $i - 2, 1) != "\\" || substr($css, $i - 3, 2) == "\\\\"))
								{
								$inString	= false;
								$quoteChar	= null;
								}
							elseif (in_array($c,$sVariableDeclEol))
								{
								$value 		= trim(substr($buffer, 0, -1));
								$r[]		= array(self::T_CSS3_VARIABLE_DECLARATION, $variable, $value, $varScope);
								$buffer		= "";
								break;
								}
							$buffer .= $c = substr($css, $i, 1);
							}
						}
					// End of variables block
					elseif ($c == "}")
						{
						$r[]		= array(self::T_CSS3_VARIABLES_END);
						$buffer		= "";
						$varScope	= $sDefaultScope;
						break;
						}
					$buffer .= $c = substr($css, $i, 1);
					}
				}
			// Media block
			elseif ($c == "@" && strtolower(substr($css, $i, 6)) == "@media")
				{
				$buffer = "";
				for ($i = $i + 6 ;$i < $l; $i++)
					{
					if (substr($css, $i, 1) == "{") {break;}
					$buffer .= substr($css, $i, 1);
					}
				$buffer 	= trim($buffer);
				$value		= $buffer != "" ? explode(",", $buffer) : $sDefaultScope;
				$r[]		= array(self::T_AT_RULE_MEDIA_START, "media", $value);
				$buffer 	= "";
				$i++;
				$mediaScope	= $value;
				$inMedia	= true;
				}
			// End of media blocks
			elseif ($inMedia && $c == "}")
				{
				$r[]		= array(self::T_AT_RULE_MEDIA_END);
				$buffer 	= "";
				$mediaScope	= $sDefaultScope;
				$isInBlock	= false;
				}
			// At-Rule
			elseif ($c == "@")
				{
				$buffer = "";
				for (;$i < $l; $i++)
					{
					if (!$inString && in_array($c, $sQuoteChars))
						{
						$inString	= true;
						$quoteChar	= $c;
						}
					elseif ($inString && $c === $quoteChar && (substr($css, $i - 2, 1) != "\\" || substr($css, $i - 3, 2) == "\\\\"))
						{
						$inString	= false;
						$quoteChar	= null;
						}
					elseif (in_array($c, $sAtRuleEol))
						{
						//$value	= trim(substr($buffer, 0, -1));
						$buffer		= trim($buffer, $sAtRuleTrim);
						if (($pos = strpos($buffer, " ")) !== false)
							{
							$rule = substr($buffer, 1, $pos -1);
							$value = trim(substr($buffer, $pos));
							$r[] = array(self::T_AT_RULE, $rule, $value);
							}
						$buffer		= "";
						break;
						}
					$buffer .= $c = substr($css, $i, 1);
					}
				}
			// Ruleset
			elseif ($c == "{")
				{
				$r[]		= array(self::T_RULESET_START);
				// Selector
				$selCss		= trim($buffer, $sSelValueTrim);
				$selectors	= array();
				$buffer		= "";
				for ($i2 = 0, $l2 = strlen($selCss) ;$i2 < $l2; $i2++)
					{
					if (!$inString && in_array($c, $sQuoteChars))
						{
						$inString	= true;
						$quoteChar	= $c;
						}
					elseif ($inString && $c === $quoteChar && (substr($selCss, $i2 - 2, 1) != "\\" || substr($selCss, $i2 - 3, 2) == "\\\\"))
						{
						$inString	= false;
						$quoteChar	= null;
						}
					elseif (!$inString && $c == ",")
						{
						$selectors[] = trim($buffer, $sDefaultTrim . ",");
						$buffer = "";
						}
					$buffer .= $c = substr($selCss, $i2, 1);
					}
				$selectors[] = trim($buffer, $sDefaultTrim . ",");
				$selectors 	= array_filter(array_map("trim", $selectors));
				$r[]		= array(self::T_SELECTORS, $selectors);
				$r[]		= array(self::T_DECLARATIONS_START);
				$buffer		= "";
				for ($i++ ;$i < $l; $i++)
					{
					// Comment (ruleset level)
					if (substr($css, $i, 2) == "/*")
						{
						for (;$i < $l; $i++)
							{
							if (substr($css, $i, 2) == "*/") {break;}
							$buffer .= substr($css, $i, 1);
							}
						$comment	= trim($buffer . substr($css, $i, 2));
						$r[]		= array(self::T_COMMENT, $comment);
						$i			= $i + 2;
						$buffer 	= "";
						}
					// Declaration
					elseif ($c == ":")
						{
						//$property = trim(substr($buffer, 0, -1));
						$property	= trim($buffer, $sDeclPropertyTrim);
						$buffer		= "";
						for (;$i < $l; $i++)
							{
							if (!$inString && in_array($c, $sQuoteChars))
								{
								$inString	= true;
								$quoteChar	= $c;
								}
							elseif ($inString && $c === $quoteChar && (substr($css, $i - 2, 1) != "\\" || substr($css, $i - 3, 2) == "\\\\"))
								{
								$inString	= false;
								$quoteChar	= null;
								}
							elseif (in_array($c, $sDeclEol))
								{
								//$value	= trim(substr($buffer, 0, -1));
								$value		= trim($buffer, $sDeclValueTrim);
								$r[]		= array(self::T_DECLARATION, $property, $value, $mediaScope);
								$buffer		= "";
								break;
								}
							$buffer .= $c = substr($css, $i, 1);
							}
						}
					// End of Ruleset
					elseif ($c == "}")
						{
						$r[] = array(self::T_DECLARATIONS_END);
						break;
						}
					$buffer .= $c = substr($css, $i, 1);
					}
				$buffer = "";
				}
			}
		return $r;
		}
	}
/**#@-*/
?>