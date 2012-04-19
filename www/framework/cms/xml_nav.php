<?php

namespace depage\cms;

class xml_nav {
    
    const ACTIVE_STATUS = 'active';
    const PARENT_STATUS = 'parent-of-active';
    
    // parse() {{{
    /**
     * Parse
     * 
     * @param $xml_path - Path to the navigation.xml document
     * @param $base_url - Base URL to strip from REQUEST_URI when matching.
     * @param $index    - URL to match in the XML for index page '/'
     * 
     * @return 
     */
    public static function parse($xml_path, $xsl_path, $base_url='', $index='home') {
        $xml = new \DOMDocument();
        
        if (!$xml->load($xml_path))
        {
            throw new \exception('Could not load the navigation XML file.');
        }
        
        self::addUrls($xml->documentElement);
        
        self::addStatus($xml, $base_url, $index);
        
        $html = self::transform($xml,$xsl_path); 
        
        return $html;
    }
    // }}}
    
    // addStatus() {{{
    /**
     * Add Status
     * 
     * Checks the request URL and sets the status of the active page
     * in the XML DOM tree.
     * 
     * Sets parent folder statuses to 'parent-of-active'.
     *
     * @param \DOMDocument $xml
     * 
     * @return \DOMDocument $xml 
     */
    private static function addStatus(\DOMDocument $xml, $base_url='', $index='home')
    {
        // trim the base url (e.g. www/en)
        $url = trim(ltrim($_SERVER['REQUEST_URI'], $base_url), '/');
        
        // if url is empty match to index page
        $url = '/' . (empty($url) ? $index : $url);
        
        $xpath = new \DOMXpath($xml);
        
        $pages = $xpath->query("//pg:page[@url='{$url}']");
        
        if($pages->length)
        {
            $page = $pages->item(0);
            
            $page->setAttribute('status', self::ACTIVE_STATUS);
            
            $ancestors = $xpath->query("//pg:page[@url='{$url}']/ancestor::pg:*");
            
            foreach($ancestors as $ancestor)
            {
                $ancestor->setAttribute('status', self::PARENT_STATUS);
            }
        }
        
        return $xml;
    }
    // }}}
    
    // addUrls()
    /**
     * Add Urls
     * 
     * Adds a url attribute to each page in the XML DOM tree.
     * The url is built from the page name and the names of ancestor folders.
     * e.g. folder1/folder2/page1
     * 
     * @param \DOMDocument $xml
     * 
     * @return \DOMDocument $xml
     */
    private static function addUrls($node, $url = '/')
    {
        if($node->nodeName == 'pg:folder') {
            $url .= \html::get_url_escaped($node->getAttribute('name')) . '/';
        } elseif ($node->nodeName == 'pg:page') {
            $node->setAttribute('url',
                $url . \html::get_url_escaped($node->getAttribute('name')));
        }
        if ($node->hasChildNodes()) {
            foreach($node->childNodes as $child){
                self::addUrls($child, $url);
            }
        }
        
        /*
        // Alternative using XPATH - remove?

        $xpath = new \DOMXpath($xml);
        
        $pages = $xpath->query("//pg:page");
        
        foreach($pages as $page)
        {
            $uri = '/';
            
            $folders = $xpath->query("ancestor::pg:folder", $page);
            
            foreach($folders as $folder)
            {
                $uri .= $folder->getAttribute('name') .'/';
            }
            
            $uri .= \html::get_url_escaped($page->getAttribute('name')) . '/';
            
            $page->setAttribute('url', $uri);
        }
        
        return $xml;
        */
    }
    // }}
    
    // {{{ transform()
    /**
     * Transform
     * 
     * Transforms the XML navigation to an HTML format
     * according to the XSL provided.
     * 
     * @param \DOMDocument $xml
     * @param \DOMDocument $xslt
     *  
     * @return $html
     */
    private static function transform(\DOMDocument $xml, $xsl_path) {
        $xsl = new \DOMDocument();
        
        if (!$xsl->load($xsl_path))
        {
            throw new \exception('Could not load the navigation XSL file.');
        }
        
        $xslt = new \XSLTProcessor();
        $xslt->importStylesheet($xsl);
        
        libxml_use_internal_errors(true);
        
        if (!$html = $xslt->transformToXml($xml))
        {   
            var_dump(libxml_get_errors());
            
            $error = libxml_get_last_error();
            $error = empty($error)? 'Could not transform the navigation XML document.' : $error->message;
            
            throw new \exception($error);
        }
        
        return $html;
    }
    // }}}
}

?>
