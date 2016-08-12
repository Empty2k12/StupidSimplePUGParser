<?php

/**
 * This class parses PUG (jade-lang.com) and outputs properly formatted HTML
 *
 * This is a one class parser which allows for easy development using the templating language
 * PUG. It is in no way feature complete.
 *
 * @copyright Gero Gerke 2016
 * @author Gero Gerke <11deutron11@gmail.com>
 * @license The MIT License
 * @version Version 1.0.4
 * @link https://github.com/Empty2k12/StupidSimplePUGParser
 * @since Version 1.0.0
 *
 */
class StupidSimplePugParser {

    const REGEX_CODE = '/([a-z0-9\/?|\|]+).*$/';
    const REGEX_COMMENT = '/\/\/ (.+).*$/';
    const REGEX_BLOCKING_COMMENT = "/\/\/- (.+).*$/";
    const REGEX_ATTR = '/^[^\(=\-]+\(([^\)]+)\).*$/';
    const REGEX_STYLE = '/^[^ \.#\(=\-]*([\.#][^ \(=]*).*$/';
    const REGEX_TEXT = '/^[^ \(=\-]+(\([^\)]*\))? (.*)$/';
    const SKIP_STRING = 'hopefullynostringwilleverstartwiththsitext';
    const SELFCLOSING_TAGS = array('area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr');

    static $DOCTYPE_DECLARATIONS = array(
        'xml' => '<?xml version="1.0" encoding="utf-8" ?>',
        'transitional' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile' => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    );
    private $code = "";
    private $options = array();

    static function create() {
        return new StupidSimplePugParser();
    }

    /**
     * Initialises the class with PUG Code
     * 
     * @param string $code
     */
    function withCode($code) {
        $this->code = $code;
        return $this;
    }

    /**
     * Initialises the class from a file
     * 
     * @param string $code
     */
    function withFile($filename) {
        $this->code = file_get_contents($filename);
        return $this;
    }

    /**
     * Sets the parser options
     * 
     * @param string $code
     */
    function setOptions($options) {
        $this->options = $options;
        return $this;
    }

    /**
     * Parses a string of PUG code to HTML
     *
     * @param string $pugCode PUG Code
     * @param array $options Options Array
     * @return string HTML Code
     *
     * @todo More Features such as Mixins
     */
    function toHtml() {

        $key = hash('ripemd128', serialize($this->options));
        if ($this->should_cache() && !file_exists($this->get_cache_dir())) {
            mkdir($this->get_cache_dir(), 0777, true);
        }
        if ($this->should_cache() && file_exists($this->get_cache($key))) {
            return gzuncompress(file_get_contents($this->get_cache($key)));
        }

        $linesToClose = [];
        $html = null;

        foreach ($this->code_to_lines($this->code) as $lineNumber => $line) {

            $lineIndentation = $this->get_line_indentation($line) + $this->get_additional_indent();
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $newLinesToClose = array();
            $closingLine = null;
            foreach ($linesToClose as $previousIndentation => $previousClosing) {
                if ($previousIndentation >= $lineIndentation) {
                    $closingLine = $previousClosing[0] . $this->should_lb($previousIndentation, $lineIndentation) . $this->get_indentation($previousIndentation - 2) . $closingLine;
                } else {
                    $newLinesToClose[$previousIndentation] = $previousClosing;
                }
            }
            $linesToClose = $newLinesToClose;

            if ($line !== self::SKIP_STRING) {
                $element = $this->format_element($line, $lineIndentation);
                $htmlBlock = $element[0];
                $linesToClose[$lineIndentation] = array($element[1], $lineNumber);
            } else {
                $htmlBlock = $this->str_replace_first(self::SKIP_STRING, "", $line);
            }

            # format template
            if (empty($htmlBlock)) {
                $html .= $closingLine;
            } else {
                $html .= $closingLine . $this->should_lb($lineNumber) . $this->get_indentation($lineIndentation) . ltrim($htmlBlock);
            }
        }

        if ($this->should_cache()) {
            file_put_contents($this->get_cache($key), gzcompress($html));
        }

        return $html;
    }
    
    /**
     * Retuns true is a CSRF Token is supplied in the options
     * 
     * @return boolean
     */
    function has_csrf_token() {
        return array_key_exists("csrfToken", $this->options);
    }
    
    /**
     * Returns the CSRF Token if supplied, else null
     * 
     * @return CSRF Token
     */
    function get_csrf_token() {
        if($this->has_csrf_token()) {
            return $this->options['csrfToken'];
        }
    }

    
    /**
     * Returns true if the option is set to cache
     * 
     * @return boolean
     */
    function should_cache() {
        return array_key_exists('cache', $this->options) && $this->options['cache'] === TRUE;
    }

    /**
     * Returns full cache path for a options key
     * 
     * @param string $key
     * @return string Filepath
     */
    function get_cache($key) {
        return $this->get_cache_dir() . $key . '.cache';
    }

    /**
     * Gets the cache dir from options, else "pug_cache/"
     * 
     * @return string Cache Directory
     */
    function get_cache_dir() {
        $cacheDir = 'pug_cache/';
        if (array_key_exists('cacheDir', $this->options)) {
            $cacheDir = $this->options['cacheDir'];
        }
        return $cacheDir;
    }

    /**
     * Returns the source code split to lines
     *
     * @param string $pugCode PUG Code
     * @return array Lines
     */
    function code_to_lines($pugCode) {
        $source = str_replace("\r", "", $pugCode);
        return explode("\n", $source . "\n" . self::SKIP_STRING);
    }

    /**
     * Return by how many spaces a string is tailed
     *
     * @param string $line The Line
     * @return integer Spaces Indentation
     */
    function get_line_indentation($line) {
        return mb_strlen($line) - mb_strlen(ltrim($line));
    }

    /**
     * Returns $indent x '\t'
     *
     * @param integer $indent The Indentation to be converted to a string
     * @return string The Indentation
     */
    function get_indentation($indent) {
        if ($indent > 0) {
            $fileIndentedBy = 2;
            if (array_key_exists('filesIndentedBy', $this->options)) {
                $fileIndentedBy = $this->options['filesIndentedBy'];
            }
            return str_repeat("\t", $indent / $fileIndentedBy);
        }
    }

    /**
     * Checks if a linebreak should be applied
     *
     * @param integer $previousIndentation The Indentation of the previous code block
     * @param integer $lineIndentation Current line indentation
     * @return string New line if we should break, null else
     */
    function should_lb($previousIndentation, $lineIndentation = 0) {
        if ($previousIndentation > $lineIndentation) {
            return "\n";
        }
    }

    /**
     * Formats the raw line to a valid HTML block
     *
     * @param string $line Raw line
     * @param int $currentIndent Additional indentation to be applied
     * @return string Valid HTML block
     */
    function format_element($line, $currentIndent) {
        if ($this->is_comment($line)) {
            return $this->get_formatted_comment($line);
        }
        if ($this->is_blocking_comment($line)) {
            return array(null, null);
        }
        $extractedCode = $this->extract_html_tag($line);
        $code = $this->pipe_to_p($extractedCode);
        $tag_content = $this->extract_tag_contents($line);
        if ($this->is_doctype_operator($code)) {
            return $this->get_formatted_doctype($tag_content);
        }
        if ($this->is_include_operator($code)) {
            return $this->handle_include($tag_content, $currentIndent);
        }
        $attr = $this->extract_attrs($line);
        $style = $this->extract_style($line);

        if (array_key_exists("additionalIndent", $this->options)) {
            $additionalIndent = $this->options['additionalIndent'];
        }
        return $this->get_tag($code, $attr, $style, $tag_content, $this->get_indentation($currentIndent));
    }

    /**
     * Gets the additiona indent from options, else 0
     * 
     * @return integer additionalIndent from options, else 0
     */
    function get_additional_indent() {
        $additionalIndent = 0;
        if (array_key_exists("additionalIndent", $this->options)) {
            $additionalIndent = $this->options['additionalIndent'];
        }
        return $additionalIndent;
    }

    /**
     * Extracts Classes and Id's and formats them to HTML
     *
     * @param string $line PUG Code
     * @return string Formatted class and id names
     */
    function extract_style($line) {
        $style = null;
        if (preg_match(self::REGEX_STYLE, $line)) {
            $styleAllPlain = preg_replace(self::REGEX_STYLE, ' \1', $line);
            $styleAll = preg_split('/([.#])/', $styleAllPlain, NULL, PREG_SPLIT_DELIM_CAPTURE);
            for ($i = 1; $i < count($styleAll); $i += 2) {
                if ($styleAll[$i] == '.') {
                    $styleClass[] = $styleAll[$i + 1];
                } else if ($styleAll[$i] == '#') {
                    $styleId[] = $styleAll[$i + 1];
                }
            }
            if (isset($styleClass) && is_array($styleClass)) {
                $style .= ' class="' . implode(' ', $styleClass) . '"';
            }
            if (isset($styleId) && is_array($styleId)) {
                $style .= ' id="' . implode(' ', $styleId) . '"';
            }
        }
        return $style;
    }

    /**
     * Extracts Attributes and returns them as a string
     *
     * @param string $line PUG Code
     * @return string Attributes
     */
    function extract_attrs($line) {
        if (preg_match(self::REGEX_ATTR, $line)) {
            return preg_replace(self::REGEX_ATTR, ' \1', $line);
        }
    }

    /**
     * Extracts Anything that is not a PUG Tag from the line
     *
     * @param string $line PUG Code
     * @return string Line Contents
     */
    function extract_tag_contents($line) {
        if (preg_match(self::REGEX_TEXT, $line)) {
            $text = preg_replace(self::REGEX_TEXT, '\2', $line);
            return $this->evaluate_variables($text);
        }
    }

    /**
     * Replaces Variables that exist with the repective text and variables that don't exist with !!{varName}
     * 
     * @param string $text Line Content
     * @return string Text with replaced Variables
     */
    function evaluate_variables($text) {
        while (preg_match("/#\{(.*)\}/isU", $text)) {
            $text = preg_replace_callback("/#\{(.*)\}/isU", function($matches) use($options) {
                return $this->return_variable($matches[1]);
            }, $text);
        }
        return $text;
    }

    /**
     * Gets a variable content, !!{varName} is it's not existant
     * 
     * @param string $match varName
     * @return string Variable if key exists, !!{varName} else
     */
    function return_variable($match) {
        $textReturn = "!!{" . $match . "}";
        if (array_key_exists('variables', $this->options) && array_key_exists($match, $this->options['variables'])) {
            $textReturn = $this->options['variables'][$match];
        }
        return $textReturn;
    }

    /**
     * Extracts the HTML Tag of the line
     *
     * @param string $line PUG Line
     * @return string HTML Tag
     */
    function extract_html_tag($line) {
        if (preg_match(self::REGEX_CODE, $line)) {
            return preg_replace(self::REGEX_CODE, '\1', $line);
        }
    }

    /**
     * Converts the pipe operator to a valid P element
     *
     * @param string $code PUG Operator
     * @return string p if |
     */
    function pipe_to_p($code) {
        if ($code === "|") {
            return "p";
        }
        return $code;
    }

    /**
     * Formats doctype to use a predefined doctype if available or passes the line content as formatted doctype
     *
     * @param string $line_content Doctype Identifier or Custom Doctype
     * @return string HTML doctype declaration
     */
    function get_formatted_doctype($line_content) {
        if (array_key_exists($line_content, self::$DOCTYPE_DECLARATIONS)) {
            return array(self::$DOCTYPE_DECLARATIONS[$line_content], null);
        } else {
            return array("<!DOCTYPE $line_content>", null);
        }
    }

    /**
     * Parse Includes deep
     *
     * @param type $includeFile Filename or Path
     * @param array $options Options
     * @return type     /
     */
    function handle_include($includeFile, $currentIndent) {
        $options = $this->options;
        $options['additionalIndent'] = $currentIndent;
        $html = StupidSimplePugParser::create()
                ->withFile($includeFile)
                ->setOptions($options)
                ->toHtml();
        return array($html, null);
    }

    /**
     * Return the formatted comment
     *
     * @param type $line Line
     * @return type Formatted Comment
     */
    function get_formatted_comment($line) {
        $comment = preg_replace(self::REGEX_COMMENT, '\1', $line);
        return array("<!-- $comment -->", null);
    }

    /**
     * Gets the formatted tag
     *
     * @param type $code
     * @param type $attr
     * @param type $style
     * @param type $tag_content
     * @return type     /
     */
    function get_formatted_tag($code, $attr, $style, $tag_content, $indent) {
        $output = "<$code$attr$style>$tag_content";
        $closing = "</$code>";
        if ($this->is_form_tag($code) && $this->has_csrf_token()) {
            $csrfToken = $this->get_csrf_token();
            $output .= "\n\t" . $indent . "<input type='hidden' name='token' value='$csrfToken'>";
        }
        return array($output, $closing);
    }

    /**
     * Gets a normal tag if normal, else gets a selfclosing tag
     *
     * @param type $code
     * @param type $attr
     * @param type $style
     * @param type $tag_content
     * @return type     /
     */
    function get_tag($code, $attr, $style, $tag_content, $indent) {
        if (in_array($code, self::SELFCLOSING_TAGS) || $this->str_endswith($code, "/")) {
            $code = str_replace("/", "", $code);
            return array("<$code$attr$style/>", null);
        } else {
            return $this->get_formatted_tag($code, $attr, $style, $tag_content, $indent);
        }
    }

    /**
     * Returns true when the operator is a doctype operator
     */
    function is_doctype_operator($code) {
        return $code === "doctype";
    }

    /**
     * Returns true when the operator is a include operator
     */
    function is_include_operator($code) {
        return $code === "include";
    }

    /**
     * Returns true when the atg is a form tag
     */
    function is_form_tag($code) {
        return $code === "form";
    }

    /**
     * Returns true when line is a comment
     */
    function is_comment($line) {
        return preg_match(self::REGEX_COMMENT, $line);
    }

    /**
     * Returns true when line is a blocking comment
     */
    function is_blocking_comment($line) {
        return preg_match(self::REGEX_BLOCKING_COMMENT, $line);
    }

    /**
     * Replaces the first appearance of $search with $replace in $subject
     *
     * @param string $search The string to be replaced
     * @param string $replace The replacement
     * @param string $subject The string where to replace
     * @return string The string with replacement
     */
    function str_replace_first($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    /**
     * Checks if a string ends with a needle
     *
     * @param string $string String to test
     * @param string $test String to test for
     * @return boolean If str endswith
     */
    function str_endswith($string, $test) {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) {
            return false;
        }
        return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }

}
