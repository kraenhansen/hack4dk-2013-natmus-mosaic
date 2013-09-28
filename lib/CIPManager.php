<?php
////////////////////////////////////////////////////////////////////////////////
//
//  CANTO INTEGRATION PLATFORM
//  Copyright 2011 Canto GmbH
//  All Rights Reserved.
//
//	NOTICE: Canto GmbH permits you to use, modify, and distribute this file
//  in accordance with the terms of the license agreement accompanying it.
//
////////////////////////////////////////////////////////////////////////////////

require_once 'constants/Parameters.php';
require_once 'classes/CIPHttpRequest.php';

/**
 * Canto Integration Platform Manager
 */
class CIPManager {

    private $baseURL;
    private $ASSET_IMPORT = '/asset/import/';
    private $METADATA_SEARCH = '/metadata/search/';
    private $METADATA_GETFIELDVALUES = '/metadata/getfieldvalues/';
    private $METADATA_SETFIELDVALUES = '/metadata/setfieldvalues/';
    private $METADATA_ASSIGNTOCATEGORIES = '/metadata/assigntocategories/';
    private $METADATA_DETACHFROMCATEGORIES = '/metadata/detachfromcategories/';
    private $METADATA_GETCATEGORIES = '/metadata/getcategories/';
    private $PREVIEW_IMAGE = '/preview/image/';

    /**
     * Create an instance of the CIPManager
     * @param string $baseURL The base URL used by CIPManager
     */
    public function CIPManager($baseURL) {
        $this->baseURL = $baseURL;
    }

    /**
     * Gets the field values
     * @param string $catalog The catalog alias name from the server configuration file
     * @param int $itemId The item identify number
     * @param string $view The view alias name from the server configuration file
     * @return array The array with the found items 
     */
    public function getFieldValue($catalog, $itemId, $view) {
        $result = null;

        $url = $this->baseURL . $this->METADATA_GETFIELDVALUES . $catalog . '/';

        if (is_null($view)) {
            $url .= $itemId;
        } else {
            $url .= $view . '/' . $itemId;
        }

        try 
        {
            $request = new CIPHttpRequest($url);
            $data = null;
            $r = $request->exec($data);
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) 
            {
                $result = json_decode($r);
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }
    
    
        /**
     * Gets the field values
     * @param string $catalog The catalog alias name from the server configuration file
     * @param int $itemId The item identify number
     * @param string $view The view alias name from the server configuration file
     * @return array The array with the found items 
     */
    public function importAsset($catalog, $view, $filename, $fields) {
        $result = null;

        $url = $this->baseURL . $this->ASSET_IMPORT . $catalog . '/';

        if (!is_null($view)) {
            $url .= $view;
        }

        try {
            $request = new CIPHttpRequest($url);
            $r = $request->uploadFile($filename, $fields);
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) {
                $result = json_decode($r);
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }

    /**
     * Generate a preview URL for the image
     * @param string $catalog The catalog alias name from the server configuration file
     * @param int $itemId The item identify number
     * @param int $maxsize The maximal size of the width and height with the aspect 
     * @param boolean $fallbackImageOnError Determine whether the fallback image should be returned in case of error
     * @return string The prepared URL for the preview image
     */
    public function previewImage($catalog, $itemId, $maxsize, $fallbackImageOnError = false) 
    {
        $url = $this->baseURL . $this->PREVIEW_IMAGE . $catalog . '/' . $itemId . '?' . PARAM_FALLBACK_IMAGE_ON_ERROR;
        if ($fallbackImageOnError) {
            $url .= '=true';
        }
        else
        {
            $url .= '=false';
        }
        
        if (isset ($maxsize))
        {
            $url .= '&' . PARAM_MAX_SIZE . '=' . $maxsize;
        }
        return $url;
    }

    /**
     * Perform a search using a query defined in the CIP configuration file. You must specify at least one of the following parameters: quicksearchstring, querystring to perform a search. If you specify more then one parameter the single queries will be joined to complex one with the AND operator.
     * @param string $catalog The catalog alias name from the server configuration file
     * @param string $view The view alias name from the server configuration file
     * @param string $quickSearchText The text to perform a quicksearch with. It can be combined with a normal search query string or named query. 
     *                                The result depends on the DAM system and its configuration.
     * @param string $queryString The complete query string as expected by the DAM system. It can be combined with a quicksearchstring.
     * @param string $categoriesNameField The name of the categories field i.e.: Categories
     * @param int $categoryId The id of the category
     * @return array The array with the found items  
     */
    public function findItems($catalog, $view, $quickSearchText, $queryString = null, $categoryId = null, $categoriesNameField = null) 
    {
        $result = null;

        $url = $this->baseURL . $this->METADATA_SEARCH . $catalog . '/' . $view;
        
        $data= "";

        if (!is_null($quickSearchText))
        {
            $data .= PARAM_QUICKSEARCH_STRING . '=' . urlencode($quickSearchText);
        }
        
        if (!is_null ($categoryId))
        {
            $queryString = $this->prepareCategorySearchQuery($queryString, $categoryId, FALSE, $categoriesNameField);
        }
        
        if (!is_null ($queryString))
        {
            if ($data != "")
            {
                $data .= '&';
            }
            $data .= PARAM_QUERY_STRING . '=' . urlencode($queryString);
        }

        try {
            $request = new CIPHttpRequest($url);
            $r = $request->exec($data);
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) 
            {
                $result = json_decode($r);
            }
            else 
            {
                echo "Error " . $r;
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }
    
    /**
     * Set filed values
     * @param array $items The array with the items field values to be stored on the server
     * @param string $catalog The catalog alias name from the server configuration file
     * @return type 
     */
    public function setFieldValues($items, $catalog) {
        $result = null;

        $url = $this->baseURL . $this->METADATA_SETFIELDVALUES. $catalog;
        
        $json = json_encode(array(JSON_ITEMS => $items));

        try {
            $request = new CIPHttpRequest($url);
            $r = $request->sendJson($json);
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) {
                $result = json_decode($r);
            }
            else
            {
                echo "Error " . $r;
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }
    
    /**
     * Gets categories 
     * @param string $catalog The catalog alias name from the server configuration file
     * @param string $view The view alias name from the server configuration file
     * @param int $levels This parameter specifies whether you want the result to contain not just the direct sub-categories of the given parent 
     * but the whole sub-tree including all categories down to the given level.
     * Possible values are:
     * - 1	(Default) Return the direct sub-categories of the given parent category only.
     * - 0	Return the requested category id only.
     * - n	(Where “n” is a positive number) Return all the categories underneath the parent category down to the “n” level. 
     *     They are returned in “depth-first”. The result nests sub-categories inside their parent category item so that the tree structure can be reconstructed. 
     *     If you specify a collection to store the result the collection will contain the category IDs as a flat list.
     * - <code>CATEGORIES_ALL_LEVELS</code> Return all the categories underneath the parent category down to the bottom level. They are returned in “depth-first”. 
     *     The result nests sub-categories inside their parent category item so that the tree structure can be reconstructed. 
     *     If you specify a collection to store the result the collection will contain the category IDs as a flat list.
     * @return array The categories from the server
     */
    public function getCategories ($catalog, $view, $levels)
    {
        $url = $this->baseURL . $this->METADATA_GETCATEGORIES . $catalog . '/' . $view;
        $data = null;
        if (!is_null($levels))
        {
            $data = PARAM_LEVELS . '=';
            $data = $data . $levels;
        }

        try {
            $request = new CIPHttpRequest($url);
            if (is_null($data))
            {
                $r = $request->exec();
            }
            else
            {
                $r = $request->exec($data);
            }
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) {
                $result = json_decode($r);
            } else {
                //$result = json_decode($r);
                echo "Error " . $r;
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }
    
    /**
     * Add category filter to the query string
     * @param string $queryString The complete query string as expected by the DAM system. It can be combined with a quicksearchstring.
     * @param string $categoryId The requested category ID
     * @param boolean $subTree Determine whether the sub categories should be included in the search
     * @param string $categoriesNameField The name of the categories field i.e.: Categories
     * @return string The prepared query 
     */
    public function prepareCategorySearchQuery ($queryString, $categoryId, $subTree, $categoriesNameField)
    {
        $retVal = $categoriesNameField;
        if ($subTree) {
            $retVal .= " is \":B " . $categoryId . ":\"";
        } else {
            $retVal .= " is :" . $categoryId . ":";
        }
        if (!is_null ($queryString)) {
            $retVal .= " && " . $queryString;
        }

        return $retVal;
    }
    
    /**
     * Assign an item to the specified category
     * @param int $itemId The item identify number
     * @param int $categoryId The category identify number
     * @param string $catalog The catalog alias name from the server configuration file
     * @return type 
     */
    public function assignToCategoryId ($itemId, $categoryId, $catalog) {

        $items = $this->getCategoriesAsJson($itemId, $categoryId);
        return $this->processCategories ($items, $catalog, $this->METADATA_ASSIGNTOCATEGORIES);
    }
    
    /**
     * Detach an item from the specified category
     * @param type $itemId The item id 
     * @param type $categoryId The category id
     * @param type $catalog The catalog alias name from the server configuration file
     * @return type 
     */
    public function detachFromCategoryId($itemId, $categoryId, $catalog) {

        $items = $this->getCategoriesAsJson($itemId, $categoryId);
        return $this->processCategories($items, $catalog, $this->METADATA_DETACHFROMCATEGORIES);
    }
    
    /**
     * Process categories 
     * @param array $items The prepared item array
     * @param string $catalog The catalog alias name from the server configuration file
     * @param string $operation The operation name
     * @return type 
     */
    private function processCategories($items, $catalog, $operation) {
        $result = null;

        $url = $this->baseURL . $operation . $catalog;

        $json = json_encode(array(JSON_ITEMS => $items));

        try {
            $request = new CIPHttpRequest($url);
            $r = $request->sendJson($json);
            $httpCode = $request->getHttpCode();

            if ($httpCode == 200) {
                $result = json_decode($r);
            } else {
                echo "Error " . $r;
            }
        } catch (HttpException $e) {
            echo $e;
        }
        return $result;
    }
    
    /**
     * Create JSON to update an item's category
     * 
     * @param int $itemId The item id
     * @param int $categoryId The category Id
     * @return array Prepared json structure as array
     */
    private function getCategoriesAsJson($itemId, $categoryId)
    {
         $items =
            array(
                array(
                    JSON_ID => $itemId,
                    $this->CATEGORIES_FIELD_NAME =>
                    array(
                        JSON_ID => $categoryId
                    )
                )
        );
        return $items;
    }
}
?>