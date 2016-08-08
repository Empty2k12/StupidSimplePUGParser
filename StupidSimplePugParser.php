<?php

class StupidSimplePugParser {

    const REGEX_CODE = '/([a-z0-9]+).*$/';
    const REGEX_ATTR = '/^[^\(=\-]+\(([^\)]+)\).*$/';
    const REGEX_STYLE = '/^[^ \.#\(=\-]*([\.#][^ \(=]*).*$/';
    const REGEX_TEXT = '/^[^ \(=\-]+(\([^\)]*\))? (.*)$/';
    const SKIP_STRING = "hopefullynostringwilleverstartwiththsitext";
    const SELFCOSING_TAGS = array("area", "base", "br", "col", "command", "embed", "hr", "img", "input", "keygen", "link", "meta", "param", "source", "track", "wbr");

    static function parseFile($fileName) {
        $source_code = file_get_contents($fileName);
        return self::parseCode($source_code);
    }

    #Main Parser
    #TODO: options? (variables, ...)
    #TODO: more features
    static function parseCode($pugCode) {
        $source = str_replace("\r", "", $pugCode);
        $lines = explode("\n", $source . "\n" . self::SKIP_STRING);
        $closing = [];
        $html = null;

        foreach ($lines as $n => $line) {

            $lineIndentation = mb_strlen($line) - mb_strlen(ltrim($line));
            $line = trim($line);
            $indentSpaces = str_repeat("\t", $lineIndentation / 2);

            if (!empty($line)) {

                $newClosing = array();
                $closingLine = null;
                foreach ($closing as $key => $item) {
                    if ($key >= $lineIndentation) {
                        $closingLine = $item[0] . self::should_lb($key, $lineIndentation, $key - 2) . $closingLine;
                    } else {
                        $newClosing[$key] = $item;
                    }
                }
                $closing = $newClosing;

                if ($line !== self::SKIP_STRING) {
                    $element = self::format_element($line);
                    $line = $element[0];
                    $closing[$lineIndentation] = array($element[1], $n);
                } else {
                    $line = self::str_replace_first(self::SKIP_STRING, "", $line);
                }
                # format template
                $html .= $closingLine . self::lb($n) . $indentSpaces . $line;
            }
        }
        return $html;
    }

    static function lb($lineNum) {
        if ($lineNum !== 0) {
            return "\n";
        }
    }

    static function should_lb($key, $lineIndentation, $indent) {
        if ($key > $lineIndentation) {
            return "\n" . str_repeat("\t", $indent / 2);
        }
        return "";
    }

    static function format_element($line) {
        $attr = $code = $style = $styleAll = $tag_content = null;
        
        if (preg_match(self::REGEX_TEXT, $line)) {
            $tag_content = preg_replace(self::REGEX_TEXT, '\2', $line);
        }

        if (preg_match(self::REGEX_CODE, $line)) {
            $code = preg_replace(self::REGEX_CODE, '\1', $line);
        }
        
        if (preg_match(self::REGEX_ATTR, $line)) {
            $attr = preg_replace(self::REGEX_ATTR, ' \1', $line);
        }
        
        if (preg_match(self::REGEX_STYLE, $line)) {
            if (!$code) {
                $code = 'div';
            }
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

        if(in_array($code, self::SELFCOSING_TAGS)) {
            return array("<$code$attr$style/>", null); 
        } else {
            $output = "<$code$attr$style>$tag_content";
            $closing = "</$code>";
            return array($output, $closing); 
        }
    }

    static function str_replace_first($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

}