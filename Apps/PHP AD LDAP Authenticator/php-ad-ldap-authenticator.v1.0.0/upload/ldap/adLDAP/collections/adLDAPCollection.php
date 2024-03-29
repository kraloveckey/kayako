<?php
/**
 * PHP LDAP CLASS FOR MANIPULATING ACTIVE DIRECTORY 
 * Version 4.0.4
 * 
 * PHP Version 5 or 7.x with SSL and LDAP support
 * 
 * http://adldap.sourceforge.net/
 * 
 * 
 * @category ToolsAndUtilities
 * @package adLDAP
 * @subpackage Collection
 * @version 4.0.4
 * @link http://adldap.sourceforge.net/
 */

abstract class adLDAPCollection
{
    /**
    * The current adLDAP connection via dependency injection
    * 
    * @var adLDAP
    */
    protected $adldap;
    
    /**
    * The current object being modifed / called
    * 
    * @var mixed
    */
    protected $currentObject;
    
    /**
    * The raw info array from Active Directory
    * 
    * @var array
    */
    protected $info;
    
    public function __construct($info, adLDAP $adldap) 
    {
        $this->setInfo($info);   
        $this->adldap = $adldap;
    }
    
    /**
    * Set the raw info array from Active Directory
    * 
    * @param array $info
    */
    public function setInfo(array $info) 
    {
        if ($this->info && sizeof($info) >= 1) {
            unset($this->info);
        }
        $this->info = $info;   
    }
    
    /**
    * Magic get method to retrieve data from the raw array in a formatted way
    * 
    * @param string $attribute
    * @return mixed
    */
    public function __get($attribute)
    {
        if (isset($this->info[0]) && is_array($this->info[0])) {
            foreach ($this->info[0] as $keyAttr => $valueAttr) {
                if (strtolower($keyAttr) == strtolower($attribute)) {
                    if ($this->info[0][strtolower($attribute)]['count'] == 1) {
                        return $this->info[0][strtolower($attribute)][0];   
                    }
                    else {
                        $array = array();
                        foreach ($this->info[0][strtolower($attribute)] as $key => $value) {
                            if ((string)$key != 'count') {
                                $array[$key] = $value;
                            } 
                        }  
                        return $array;   
                    }
                }   
            }
        }
        else {
            return NULL;   
        }
    }    
    
    /**
    * Magic set method to update an attribute
    * 
    * @param string $attribute
    * @param string $value
    * @return bool
    */
    abstract public function __set($attribute, $value);
    
    /** 
    * Magic isset method to check for the existence of an attribute 
    * 
    * @param string $attribute 
    * @return bool 
    */ 
    public function __isset($attribute) {
        if (isset($this->info[0]) && is_array($this->info[0])) { 
            foreach ($this->info[0] as $keyAttr => $valueAttr) { 
                if (strtolower($keyAttr) == strtolower($attribute)) { 
                    return true; 
                } 
            } 
        } 
        return false; 
     } 
}
?>
