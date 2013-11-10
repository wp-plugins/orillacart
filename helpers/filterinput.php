<?php

class FilterInput {

    protected $tagsArray; // default = empty array
    protected $attrArray; // default = empty array
    protected $tagsMethod; // default = 0
    protected $attrMethod; // default = 0
    protected $xssAuto; // default = 1
    protected $tagBlacklist = array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml');
    protected $attrBlacklist = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc'); // also will strip ALL event handlers

    protected function __construct($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) {
        // Make sure user defined arrays are in lowercase
        $tagsArray = array_map('strtolower', (array) $tagsArray);
        $attrArray = array_map('strtolower', (array) $attrArray);

        // Assign member variables
        $this->tagsArray = $tagsArray;
        $this->attrArray = $attrArray;
        $this->tagsMethod = $tagsMethod;
        $this->attrMethod = $attrMethod;
        $this->xssAuto = $xssAuto;
    }

    static public function getInstance($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) {
        static $instances;

        $sig = md5(serialize(array($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)));

        if (!isset($instances)) {
            $instances = array();
        }

        if (empty($instances[$sig])) {
            $class = __CLASS__;
            $instances[$sig] = new self($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto);
        }

        return $instances[$sig];
    }

    public function clean($source, $type = 'string') {
        // Handle the type constraint
        switch (strtoupper($type)) {
            case 'INT' :
            case 'INTEGER' :
                // Only use the first integer value
                preg_match('/-?[0-9]+/', (string) $source, $matches);
                $result = @ (int) $matches[0];
                break;

            case 'FLOAT' :
            case 'DOUBLE' :
                // Only use the first floating point value
                preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
                $result = @ (float) $matches[0];
                break;

            case 'BOOL' :
            case 'BOOLEAN' :
                $result = (bool) $source;
                break;

            case 'WORD' :
                $result = (string) preg_replace('/[^A-Z_]/i', '', $source);
                break;

            case 'ALNUM' :
                $result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
                break;

            case 'CMD' :
                $result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
                $result = ltrim($result, '.');
                break;

            case 'BASE64' :
                $result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
                break;

            case 'STRING' :
                // Check for static usage and assign $filter the proper variable

                $result = (string) $this->_remove($this->_decode((string) $source));
                break;

            case 'ARRAY' :
                $result = (array) $source;
                break;

            case 'PATH' :
                $pattern = '/^[A-Za-z0-9_-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
                preg_match($pattern, (string) $source, $matches);
                $result = @ (string) $matches[0];
                break;

            case 'USERNAME' :
                $result = (string) preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
                break;

            default :



                // Are we dealing with an array?
                if (is_array($source)) {
                    foreach ($source as $key => $value) {
                        // filter element for XSS and other 'bad' code etc.
                        if (is_string($value)) {
                            $source[$key] = $this->_remove($this->_decode($value));
                        } else if (is_array($value)) {

                            $source[$key] = (array) array_map(array('self', 'clean'), $value);
                        }
                    }
                    $result = $source;
                } else {

                    // Or a string?
                    if (is_string($source) && !empty($source)) {
                        // filter source for XSS and other 'bad' code etc.
                        $result = $this->_remove($this->_decode($source));
                    } else {
                        // Not an array or string.. return the passed parameter
                        $result = $source;
                    }
                }
                break;
        }
        return $result;
    }

    static public function checkAttribute($attrSubSet) {
        $attrSubSet[0] = strtolower($attrSubSet[0]);
        $attrSubSet[1] = strtolower($attrSubSet[1]);
        return (((strpos($attrSubSet[1], 'expression') !== false) && ($attrSubSet[0]) == 'style') || (strpos($attrSubSet[1], 'javascript:') !== false) || (strpos($attrSubSet[1], 'behaviour:') !== false) || (strpos($attrSubSet[1], 'vbscript:') !== false) || (strpos($attrSubSet[1], 'mocha:') !== false) || (strpos($attrSubSet[1], 'livescript:') !== false));
    }

    protected function _remove($source) {
        $loopCounter = 0;

        // Iteration provides nested tag protection
        while ($source != $this->_cleanTags($source)) {
            $source = $this->_cleanTags($source);
            $loopCounter++;
        }
        return $source;
    }

    protected function _cleanTags($source) {
        /*
         * In the beginning we don't really have a tag, so everything is
         * postTag
         */
        $preTag = null;
        $postTag = $source;
        $currentSpace = false;
        $attr = '';     // moffats: setting to null due to issues in migration system - undefined variable errors
        // Is there a tag? If so it will certainly start with a '<'
        $tagOpen_start = strpos($source, '<');

        while ($tagOpen_start !== false) {
            // Get some information about the tag we are processing
            $preTag .= substr($postTag, 0, $tagOpen_start);
            $postTag = substr($postTag, $tagOpen_start);
            $fromTagOpen = substr($postTag, 1);
            $tagOpen_end = strpos($fromTagOpen, '>');

            // Let's catch any non-terminated tags and skip over them
            if ($tagOpen_end === false) {
                $postTag = substr($postTag, $tagOpen_start + 1);
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // Do we have a nested tag?
            $tagOpen_nested = strpos($fromTagOpen, '<');
            $tagOpen_nested_end = strpos(substr($postTag, $tagOpen_end), '>');
            if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
                $preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
                $postTag = substr($postTag, ($tagOpen_nested + 1));
                $tagOpen_start = strpos($postTag, '<');
                continue;
            }

            // Lets get some information about our tag and setup attribute pairs
            $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
            $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
            $tagLength = strlen($currentTag);
            $tagLeft = $currentTag;
            $attrSet = array();
            $currentSpace = strpos($tagLeft, ' ');

            // Are we an open tag or a close tag?
            if (substr($currentTag, 0, 1) == '/') {
                // Close Tag
                $isCloseTag = true;
                list ($tagName) = explode(' ', $currentTag);
                $tagName = substr($tagName, 1);
            } else {
                // Open Tag
                $isCloseTag = false;
                list ($tagName) = explode(' ', $currentTag);
            }

            /*
             * Exclude all "non-regular" tagnames
             * OR no tagname
             * OR remove if xssauto is on and tag is blacklisted
             */
            if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) {
                $postTag = substr($postTag, ($tagLength + 2));
                $tagOpen_start = strpos($postTag, '<');
                // Strip tag
                continue;
            }

            /*
             * Time to grab any attributes from the tag... need this section in
             * case attributes have spaces in the values.
             */
            while ($currentSpace !== false) {
                $attr = '';
                $fromSpace = substr($tagLeft, ($currentSpace + 1));
                $nextSpace = strpos($fromSpace, ' ');
                $openQuotes = strpos($fromSpace, '"');
                $closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;

                // Do we have an attribute to process? [check for equal sign]
                if (strpos($fromSpace, '=') !== false) {
                    /*
                     * If the attribute value is wrapped in quotes we need to
                     * grab the substring from the closing quote, otherwise grab
                     * till the next space
                     */
                    if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false)) {
                        $attr = substr($fromSpace, 0, ($closeQuotes + 1));
                    } else {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                } else {
                    /*
                     * No more equal signs so add any extra text in the tag into
                     * the attribute array [eg. checked]
                     */
                    if ($fromSpace != '/') {
                        $attr = substr($fromSpace, 0, $nextSpace);
                    }
                }

                // Last Attribute Pair
                if (!$attr && $fromSpace != '/') {
                    $attr = $fromSpace;
                }

                // Add attribute pair to the attribute array
                $attrSet[] = $attr;

                // Move search point and continue iteration
                $tagLeft = substr($fromSpace, strlen($attr));
                $currentSpace = strpos($tagLeft, ' ');
            }

            // Is our tag in the user input array?
            $tagFound = in_array(strtolower($tagName), $this->tagsArray);

            // If the tag is allowed lets append it to the output string
            if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {

                // Reconstruct tag with allowed attributes
                if (!$isCloseTag) {
                    // Open or Single tag
                    $attrSet = $this->_cleanAttributes($attrSet);
                    $preTag .= '<' . $tagName;
                    for ($i = 0; $i < count($attrSet); $i++) {
                        $preTag .= ' ' . $attrSet[$i];
                    }

                    // Reformat single tags to XHTML
                    if (strpos($fromTagOpen, '</' . $tagName)) {
                        $preTag .= '>';
                    } else {
                        $preTag .= ' />';
                    }
                } else {
                    // Closing Tag
                    $preTag .= '</' . $tagName . '>';
                }
            }

            // Find next tag's start and continue iteration
            $postTag = substr($postTag, ($tagLength + 2));
            $tagOpen_start = strpos($postTag, '<');
        }

        // Append any code after the end of tags and return
        if ($postTag != '<') {
            $preTag .= $postTag;
        }
        return $preTag;
    }

    protected function _cleanAttributes($attrSet) {
        // Initialize variables
        $newSet = array();

        // Iterate through attribute pairs
        for ($i = 0; $i < count($attrSet); $i++) {
            // Skip blank spaces
            if (!$attrSet[$i]) {
                continue;
            }

            // Split into name/value pairs
            $attrSubSet = explode('=', trim($attrSet[$i]), 2);
            list ($attrSubSet[0]) = explode(' ', $attrSubSet[0]);

            /*
             * Remove all "non-regular" attribute names
             * AND blacklisted attributes
             */
            if ((!preg_match('/[a-z]*$/i', $attrSubSet[0])) || (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist)) || (substr($attrSubSet[0], 0, 2) == 'on')))) {
                continue;
            }

            // XSS attribute value filtering
            if ($attrSubSet[1]) {
                // strips unicode, hex, etc
                $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
                // strip normal newline within attr value
                $attrSubSet[1] = preg_replace('/[\n\r]/', '', $attrSubSet[1]);
                // strip double quotes
                $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
                // convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
                if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'")) {
                    $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
                }
                // strip slashes
                $attrSubSet[1] = stripslashes($attrSubSet[1]);
            }

            // Autostrip script tags
            if (self::checkAttribute($attrSubSet)) {
                continue;
            }

            // Is our attribute in the user input array?
            $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);

            // If the tag is allowed lets keep it
            if ((!$attrFound && $this->attrMethod) || ($attrFound && !$this->attrMethod)) {

                // Does the attribute have a value?
                if ($attrSubSet[1]) {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
                } elseif ($attrSubSet[1] == "0") {
                    /*
                     * Special Case
                     * Is the value 0?
                     */
                    $newSet[] = $attrSubSet[0] . '="0"';
                } else {
                    $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
                }
            }
        }
        return $newSet;
    }

    protected function _decode($source) {
        // entity decode
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        foreach ($trans_tbl as $k => $v) {
            $ttr[$v] = utf8_encode($k);
        }
        $source = strtr($source, $ttr);
        // convert decimal
        $source = preg_replace('/&#(\d+);/me', "utf8_encode(chr(\\1))", $source); // decimal notation
        // convert hex
        $source = preg_replace('/&#x([a-f0-9]+);/mei', "utf8_encode(chr(0x\\1))", $source); // hex notation
        return $source;
    }

}