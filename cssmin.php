<?php
/**
 * cssmin.php - A simple CSS minifier.
 * --
 * 
 * <code>
 * include("cssmin.php");
 * file_put_contents("path/to/target.css", cssmin::minify(file_get_contents("path/to/source.css")));
 * </code>
 * --
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * --
 *
 * @package 	cssmin
 * @author 		Joe Scylla <joe.scylla@gmail.com>
 * @copyright 	2008 Joe Scylla <joe.scylla@gmail.com>
 * @license 	http://opensource.org/licenses/mit-license.php MIT License
 * @version 	1.0.1.b1 (2008-08-07)
 */
class cssmin
	{
	/**
	 * Minifies stylesheet definitions
	 *
	 * @param	string			$css		Stylesheet definitions as string
	 * @param	string|array	$options	Array or comma speperated list of options:
	 * 										- preserve-urls: Preserves every url defined in an url()-
	 * 										expression. This option is only required if you have 
	 * 										defined really uncommon urls with multiple spaces or 
	 * 										combination of colon, semi-colon, plus sign, etc. with 
	 * 										leading or following spaces.
	 * @return	string			Minified stylesheet definitions
	 */
	public static function minify($css, $options = "")
		{
		$options = ($options == "") ? array() : (is_array($options) ? $options : explode(",", $options));
		if (in_array("preserve-urls", $options))
			{
			// Encode url() to base64
			$css = preg_replace_callback("/url\s*\((.*)\)/siU", array(self, "_encodeUrl"), $css);
			}
		// Remove comments
		$css = preg_replace("/\/\*[\d\D]*?\*\/|\t+/", " ", $css);
		// Replace CR, LF and TAB to spaces
		$css = str_replace(array("\n", "\r", "\t"), " ", $css);
		// Replace multiple to single space
		$css = preg_replace("/\s\s+/", " ", $css);
		// Remove unneeded spaces
		$css = preg_replace("/\s*({|}|\[|\]|=|~|\+|>|\||;|:|,)\s*/", "$1", $css);
		$css = trim($css);
		if (in_array("preserve-urls", $options))
			{
			// Decode url()
			$css = preg_replace_callback("/url\s*\((.*)\)/siU", array(self, "_decodeUrl"), $css);
			}
		return $css;
		}
	/**
	 * Encodes a url() expression.
	 *
	 * @param	array	$match
	 * @return	string
	 */
	private function _encodeUrl($match)
		{
		return "url(" . base64_encode(trim($match[1])) . ")";
		}
	/**
	 * Decodes a url() expression.
	 *
	 * @param	array	$match
	 * @return	string
	 */
	private function _decodeUrl($match)
		{
		return "url(" . base64_decode($match[1]) . ")";
		}
	}
?>