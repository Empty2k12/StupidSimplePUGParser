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

    /**
     * Parses the file under $pathAndName to HTML code
     *
     * @param string $pathAndName File Path and or Name
     * @param array $options Options array
     * @return string Valid HTML code
     */
    static function parseFile($pathAndName, $options = null) {
        $source_code = file_get_contents($pathAndName);
        return self::parseCode($source_code, $options);
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
    static function parseCode($pugCode, $options) {

        $linesToClose = [];
        $html = null;

        foreach (self::code_to_lines($pugCode) as $lineNumber => $line) {

            $additionalIndent = 0;
            if(array_key_exists("additionalIndent", $options)) {
                $additionalIndent = $options['additionalIndent'];
            }
            $lineIndentation = self::get_line_indentation($line) + $additionalIndent;
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $newLinesToClose = array();
            $closingLine = null;
            foreach ($linesToClose as $previousIndentation => $previousClosing) {
                if ($previousIndentation >= $lineIndentation) {
                    $closingLine = $previousClosing[0] . self::should_lb($previousIndentation, $lineIndentation) . self::get_indentation($previousIndentation - 2) . $closingLine;
                } else {
                    $newLinesToClose[$previousIndentation] = $previousClosing;
                }
            }
            $linesToClose = $newLinesToClose;

            if ($line !== self::SKIP_STRING) {
                $element = self::format_element($line, $lineIndentation, $options);
                $htmlBlock = $element[0];
                $linesToClose[$lineIndentation] = array($element[1], $lineNumber);
            } else {
                $htmlBlock = self::str_replace_first(self::SKIP_STRING, "", $line);
            }

            # format template
            if (empty($htmlBlock)) {
                $html .= $closingLine;
            } else {
                $html .= $closingLine . self::should_lb($lineNumber) . self::get_indentation($lineIndentation) . ltrim($htmlBlock);
            }
        }
        return $html;
    }

    /**
     * Returns the source code split to lines
     *
     * @param string $pugCode PUG Code
     * @return array Lines
     */
    static function code_to_lines($pugCode) {
        $source = str_replace("\r", "", $pugCode);
        return explode("\n", $source . "\n" . self::SKIP_STRING);
    }

    /**
     * Return by how many spaces a string is tailed
     *
     * @param string $line The Line
     * @return integer Spaces Indentation
     */
    static function get_line_indentation($line) {
        return mb_strlen($line) - mb_strlen(ltrim($line));
    }

    /**
     * Returns $indent x '\t'
     *
     * @param integer $indent The Indentation to be converted to a string
     * @return string The Indentation
     */
    static function get_indentation($indent) {
        if ($indent > 0) {
            return str_repeat("\t", $indent / 4);
        }
    }

    /**
     * Checks if a linebreak should be applied
     *
     * @param integer $previousIndentation The Indentation of the previous code block
     * @param integer $lineIndentation Current line indentation
     * @return string New line if we should break, null else
     */
    static function should_lb($previousIndentation, $lineIndentation = 0) {
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
    static function format_element($line, $currentIndent, $options) {
        if (self::is_comment($line)) {
            return self::get_formatted_comment($line);
        }
        if (self::is_blocking_comment($line)) {
            return array(null, null);
        }
        $extractedCode = self::extract_html_tag($line);
        $code = self::pipe_to_p($extractedCode);
        $tag_content = self::extract_tag_contents($line, $options);
        if (self::is_doctype_operator($code)) {
            return self::get_formatted_doctype($tag_content);
        }
        if (self::is_include_operator($code)) {
            if(!isset($options)) {
                $options = array();
            }
            $options['additionalIndent'] = $currentIndent;
            return self::handle_include($tag_content, $options);
        }
        $attr = self::extract_attrs($line);
        $style = self::extract_style($line);
        return self::get_tag($code, $attr, $style, $tag_content);
    }

    /**
     * Extracts Classes and Id's and formats them to HTML
     *
     * @param string $line PUG Code
     * @return string Formatted class and id names
     */
    static function extract_style($line) {
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
    static function extract_attrs($line) {
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
    static function extract_tag_contents($line, $options) {
        if (preg_match(self::REGEX_TEXT, $line)) {
            $text = preg_replace(self::REGEX_TEXT, '\2', $line);
            while (preg_match("/#\{(.*)\}/isU", $text)) {
                $text = preg_replace_callback("/#\{(.*)\}/isU", function($matches) use($options) {
                    $textReturn = "!!{" . $matches[1] . "}";
                    if(array_key_exists('variables', $options) && array_key_exists($matches[1], $options['variables'])) {
                        $textReturn = $options['variables'][$matches[1]];
                    }
                    return $textReturn;
                }, $text);
            }
            return $text;
        }
    }

    /**
     * Extracts the HTML Tag of the line
     *
     * @param string $line PUG Line
     * @return string HTML Tag
     */
    static function extract_html_tag($line) {
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
    static function pipe_to_p($code) {
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
    static function get_formatted_doctype($line_content) {
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
     * @param type $additionalIndent Additional Indentation
     * @return type     /
     */
    static function handle_include($includeFile, $options) {
        return array(self::parseFile($includeFile, $options), null);
    }

    /**
     * Return the formatted comment
     *
     * @param type $line Line
     * @return type Formatted Comment
     */
    static function get_formatted_comment($line) {
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
    static function get_formatted_tag($code, $attr, $style, $tag_content) {
        $output = "<$code$attr$style>$tag_content";
        $closing = "</$code>";
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
    static function get_tag($code, $attr, $style, $tag_content) {
        if (in_array($code, self::SELFCLOSING_TAGS) || self::str_endswith($code, "/")) {
            $code = str_replace("/", "", $code);
            return array("<$code$attr$style/>", null);
        } else {
            return self::get_formatted_tag($code, $attr, $style, $tag_content);
        }
    }

    /**
     * Returns true when the operator is a doctype operator
     */
    static function is_doctype_operator($code) {
        return $code === "doctype";
    }

    /**
     * Returns true when the operator is a include operator
     */
    static function is_include_operator($code) {
        return $code === "include";
    }

    /**
     * Returns true when line is a comment
     */
    static function is_comment($line) {
        return preg_match(self::REGEX_COMMENT, $line);
    }

    /**
     * Returns true when line is a blocking comment
     */
    static function is_blocking_comment($line) {
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
    static function str_replace_first($search, $replace, $subject) {
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
        if ($testlen > $strlen)
            return false;
        return substr_compare($string, $test, $strlen - $testlen, $testlen) === 0;
    }

}