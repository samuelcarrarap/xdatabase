<?php
    class XML2Array
    {

        private $xml = null;
        private $encoding = 'UTF-8';

        /**
         * Initialize the root XML node [optional]
         * @param $version
         * @param $encoding
         * @param $format_output
         */
        public function init($version = '1.0', $encoding = 'UTF-8', $format_output = true)
        {
            $this->xml = new DOMDocument($version, $encoding);
            $this
                ->xml->formatOutput = $format_output;
            $this->encoding = $encoding;
        }

        /**
         * Convert an XML to Array
         * @param string $node_name - name of the root node to be converted
         * @param array $arr - aray to be converterd
         * @return DOMDocument
         */
        public function &createArray($input_xml)
        {
            $xml = $this->getXMLRoot();
            if (is_string($input_xml))
            {
                $parsed = @$xml->loadXML($input_xml);
                if (!$parsed)
                {
                    return $parsed;
                    //throw new Exception('[XML2Array] Error parsing the XML string.');
                    
                }
            }
            else
            {
                if (@get_class($input_xml) != 'DOMDocument')
                {
                    $parsed = false;
                    return $parsed;
                    //throw new Exception('[XML2Array] The input XML object should be of type: DOMDocument.');
                    
                }
                $xml = $this->xml = $input_xml;
            }
            $array[$xml
                ->documentElement
                ->tagName] = $this->convert($xml->documentElement);
            $this->xml = null; // clear the xml node in the class for 2nd time use.
            return $array;
        }

        /**
         * Convert an Array to XML
         * @param mixed $node - XML as a string or as an object of DOMDocument
         * @return mixed
         */
        private function &convert($node)
        {
            $output = array();

            switch ($node->nodeType)
            {
                case XML_CDATA_SECTION_NODE:
                    $output['@cdata'] = trim($node->textContent);
                break;

                case XML_TEXT_NODE:
                    $output = trim($node->textContent);
                break;

                case XML_ELEMENT_NODE:

                    // for each child node, call the covert function recursively
                    for ($i = 0, $m = $node
                        ->childNodes->length;$i < $m;$i++)
                    {
                        $child = $node
                            ->childNodes
                            ->item($i);
                        $v = $this->convert($child);
                        if (isset($child->tagName))
                        {
                            $t = $child->tagName;

                            // assume more nodes of same kind are coming
                            if (!isset($output[$t]))
                            {
                                $output[$t] = array();
                            }
                            $output[$t][] = $v;
                        }
                        else
                        {
                            //check if it is not an empty text node
                            if ($v !== '')
                            {
                                $output = $v;
                            }
                        }
                    }

                    if (is_array($output))
                    {
                        // if only one node of its kind, assign it directly instead if array($value);
                        foreach ($output as $t => $v)
                        {
                            if (is_array($v) && count($v) == 1)
                            {
                                $output[$t] = $v[0];
                            }
                        }
                        if (empty($output))
                        {
                            //for empty nodes
                            $output = '';
                        }
                    }

                    // loop through the attributes and collect them
                    if ($node
                        ->attributes
                        ->length)
                    {
                        $a = array();
                        foreach ($node->attributes as $attrName => $attrNode)
                        {
                            $a[$attrName] = (string)$attrNode->value;
                        }
                        // if its an leaf node, store the value in @value instead of directly storing it.
                        if (!is_array($output))
                        {
                            $output = array(
                                '@value' => $output
                            );
                        }
                        $output['@attributes'] = $a;
                    }
                break;
            }
            return $output;
        }

        /*
        * Get the root XML node, if there isn't one, create it.
        */
        private function getXMLRoot()
        {
            if (empty($this->xml))
            {
                $this->init();
            }
            return $this->xml;
        }
    }
?>