<?php
namespace GIS;

/**
 * Class EndpointFactory
 *
 * @package GIS
 */
class Endpoint implements \Iterator {

    /**
     * @var String
     */
    private $_name;

    /**
     * @var \GIS\AuthProvider
     */
    private $_auth;

    /**
     * @var Array
     */
    private $_values = array();

    /**
     * @var String
     */
    private $_basePath;

    /**
     * @var String
     */
    private $_path;

    /**
     * @var Array
     */
    private $_params = array();

    /**
     * @var bool
     */
    private $_paging = false;

    /**
     * @var int
     */
    private $_currentPage = 1;

    /**
     * @var int
     */
    private $_Pages;

    /**
     * @var int
     */
    private $_currentItem = 0;

    /**
     * @var int
     */
    private $_PageItems;

    /**
     * @var mixed
     */
    private $_data;

    /**
     * @var Array
     */
    private $_keys;

    /**
     * @var bool
     */
    private $_success;

    /**
     * @param        $name
     * @param        $manifest
     * @param        $auth
     * @param string $basePath
     * @throws RequirementsException
     */
    public function __construct($name, $manifest, $auth, $basePath = 'https://gis-api.aiesec.org') {
        $this->_auth = $auth;
        $this->_name = $name;
        $this->_basePath = $basePath;

        $this->_path = str_replace('{format}', 'json', $manifest->path);
        foreach($manifest->operations as $o) {
            if($o->httpMethod == "GET") {
                if(!in_array("application/json", $o->produces)) throw new RequirementsException("Endpoint is not able to produce JSON");

                foreach($o->parameters as $p) {
                    if(in_array('{' . $p->name . '}', $this->_values)) continue;
                    if($p->name == "access_token") continue;
                    if($p->name == "page") {
                        $this->_paging = true;
                        continue;
                    }

                    $ep = new EndpointParam($p);
                    if(array_key_exists($ep->getName(), $this->_params)) {
                        if($ep->getName() != $p->name) {
                            $this->_params[$ep->getName()]->merge($ep);
                        } else {
                            $this->_params[$ep->getName()] = $ep->merge($this->_params[$ep->getName()]);
                        }
                    } else {
                        $this->_params[$ep->getName()] = $ep;
                    }
                }
                break;
            }
        }
    }

    public function __get($name) {
        if(array_key_exists($name, $this->_params)) {
            if($this->_params[$name]->hasChilds()) {
                return $this->_params[$name];
            } else {
                return $this->_params[$name]->get();
            }
        } elseif($name == "count") {
            return $this->count();
        } elseif($name == "facets") {
            return $this->facets();
        }
        return null;
    }

    public function __set($name, $value) {
        if(array_key_exists($name, $this->_params)) {
            if(is_object($value)) {
                $this->_params[$name] = $value;
            } else {
                $this->_params[$name]->set($value);
            }
        }
    }

    /**
     * @return Array Array with all possible parameters of this endpoint
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * @return String the name of the endpoint
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * This function is only to pass the instance names through the sub apis. It should not be called from outside of an API object
     * @param $values
     */
    public function setValues($values) {
        $this->_values = $values;
        foreach($this->_values as $k => $v) {
            $n = substr($k, 1, -1);
            if(array_key_exists($n, $this->_params)) unset($this->_params[$n]);
        }
    }

    // Iterator Interface
    /**
     * Return the current element
     * @return mixed
     */
    public function current() {
        if($this->_paging) {
            return $this->_data->data[$this->_currentItem];
        } else {
            return $this->_data;
        }
    }

    /**
     * Move forward to next element
     */
    public function next() {
        $this->_currentItem++;
        if(!$this->_paging) {
            if ($this->_currentItem >= $this->_PageItems) {
                $this->_currentPage++;
                if ($this->_currentPage <= $this->_Pages) {
                    $this->load();
                    $this->_currentItem = 0;
                }
            }
        }
    }

    /**
     * Return the key of the current element
     * @return String|Int
     */
    public function key() {
        return $this->_currentPage . '_' . $this->_currentItem;
    }

    /**
     * Checks if current position is valid
     * @return boolean Returns true on success or false on failure.
     */
    public function valid() {
        if($this->_paging) {
            if($this->_currentItem < $this->_PageItems && $this->_currentPage <= $this->_Pages) return true;
        } else {
            if($this->_currentItem == 0) return $this->_success;
        }
        return false;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind() {
        $this->_currentItem = 0;
        $this->_currentPage = 1;
        $this->load();
    }

    /**
     * loads the current page
     */
    private function load() {
        $this->_success = false;

        //create url
        $url = $this->_basePath;    // start with base path

        // replace dynamic api/endpoint values in endpoint path
        $e = $this->_path;
        foreach($this->_values as $n => $v) {
            $e = str_replace($n, $v, $e);
        }

        // add endpoint path to url
        $url .= $e . '?';

        // if we have paging, add current page
        if($this->_paging) {
            $url .= 'page=' . $this->_currentPage . '&';
        }

        // add all the parameters
        foreach($this->_params as $param) {
            $url .= $param->getURLString();
        }

        $token = $this->_auth->getToken();
        $req = curl_init($url . 'access_token=' . $token);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        $res = json_decode(curl_exec($req));
        curl_close($req);

        if($res == null) throw new InvalidAPIResponseException("Invalid Response on " . $url . "access_token=" . $token);

        if(isset($res->status->code) && $res->status->code != "200") {
            if($res->status->code == "401") {
                $token = $this->_auth->getNewToken();

                $req = curl_init($url . 'access_token=' . $token);
                curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
                $res = json_decode(curl_exec($req));
                curl_close($req);

                if($res == null) throw new InvalidAPIResponseException("Invalid Response on " . $url . "access_token=" . $token);

                if(isset($res->status->code) && $res->status->code != "200") throw new InvalidAPIResponseException($res->status->message);
            } else {
                throw new InvalidAPIResponseException($res->status->message);
            }
        }

        $this->_data = $res;

        if($this->_paging) {
            $this->_currentPage = $res->paging->current_page;
            $this->_Pages = $res->paging->total_pages;
            $this->_PageItems = count($res->data);
        }
        $this->_success = true;
    }

    public function count() {
        if($this->_paging) {
            if(!isset($this->_data->paging->total_items)) $this->load();
            if(isset($this->_data->paging->total_items)) return $this->_data->paging->total_items;
        } else {
            return (int)$this->_success;
        }
        return null;
    }

    public function facets() {
        if(isset($this->_data->facets)) return $this->_data->facets;
        return null;
    }
}

class EndpointParam {

    /**
     * @var Array
     */
    private $_params;

    /**
     * @var String
     */
    private $_description;

    /**
     * @var bool
     */
    private $_required;

    /**
     * @var String
     */
    private $_name;

    /**
     * @var mixed
     */
    private $_value;

    public function __construct($manifest) {
        if(strpos($manifest->name, '[') !== false) {
            $this->_params = array();
            $this->_name = substr($manifest->name, 0, strpos($manifest->name, '['));

            $a = strlen($this->_name) + 1;
            $b = strpos($manifest->name, ']', strlen($this->_name))-$a;
            $pn = substr($manifest->name, $a, $b);
            $pa = "";

            if($manifest->name != $this->_name . '[' . $pn . ']') {
                $pa = substr($manifest->name, strlen($this->_name . '[' . $pn . ']'));
            }
            $manifest->name = $pn . $pa;

            $this->_params[$pn] = new EndpointParam($manifest);
        } else {
            $this->_params = null;
            $this->_description = $manifest->description;
            $this->_required = (bool)$manifest->required;
            $this->_name = $manifest->name;
        }
    }

    /**
     * @param $name
     * @return null|mixed
     */
    public function __get($name) {
        if(is_array($this->_params)) {
            if(array_key_exists($name, $this->_params)) {
                if($this->_params[$name]->hasChilds()) {
                    return $this->_params[$name];
                } else {
                    return $this->_params[$name]->get();
                }
            }
        }
        return null;
    }

    /**
     * @param $name Name of the child parameter
     * @param $value Sets the child $name to $value if it is an object or sets the value of $name to $value if $value is not a object
     */
    public function __set($name, $value) {
        if(is_array($this->_params)) {
            if(array_key_exists($name, $this->_params)) {
                if(is_object($value) && !is_array($value)) {
                    $this->_params[$name] = $value;
                } else {
                    $this->_params[$name]->set($value);
                }
            }
        }
    }

    /**
     * @return Array|null The childs of this parameter
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * @param $param Merges the given parameter ($param) into this parameter
     * @return $this
     */
    public function merge($param) {
        if($this->_params == null) {
            $this->_params = $param->getParams();
        } else {
            $this->_params = array_merge($this->_params, $param->getParams());
        }
        return $this;
    }

    /**
     * @param mixed|null $value Sets the value of this parameter if $value is not null
     * @return mixed|null
     */
    public function value($value = null) {
        if(!$this->hasChilds()) {
            if ($value !== null) $this->_value = $value;
            return $this->_value;
        }
    }

    /**
     * @return mixed|null The value of this parameter
     */
    public function get() {
        if(!$this->hasChilds()) return $this->value();
        return null;
    }

    /**
     * @param $value Sets the value of this parameter
     */
    public function set($value) {
        if(!$this->hasChilds()) $this->value($value);
    }

    /**
     * @return String The name of the parameter
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * @return bool returns true if the api documentation states this param as required
     */
    public function isRequired() {
        return $this->_required;
    }

    /**
     * @return String The description of this parameter from the api documentation
     */
    public function getDescription() {
        return $this->_description;
    }

    /**
     * Determines if this parameter is a parent (name of a Array stacked parameter) or a child (a value)
     *
     * @return bool true if this param has childs, or else false
     */
    public function hasChilds() {
        if($this->_params !== null) return true;
        else return false;
    }

    /**
     * @param String|null $parent parent name
     * @return string Returns the value of this parameter as encoded GET parameter
     * @throws ParameterRequiredException if this parameter has no value but is required
     */
    public function getURLString($parent = null) {
        $name = $this->_name;
        if ($parent != null) $name = $parent . '[' . $name . ']';
        if($this->hasChilds()) {
            $t = "";
            foreach($this->_params as $p) {
                $t .= $p->getURLString($name);
            }
            return $t;
        } else {
            if(is_array($this->_value)) {

            } else if($this->_value != "") {
                return urlencode($name) . '=' . urlencode($this->_value) . '&';
            } elseif($this->_required) throw new ParameterRequiredException("The parameter " . $name . " is required.");
        }
        return "";
    }
}