<?php
/* SVN FILE: $Id$ */

/**
 * Short description for file.
 *
 * Long description for file
 *
 * PHP versions 4 and 5
 *
 * CakePHP :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright (c) 2006, Cake Software Foundation, Inc.
 *                     1785 E. Sahara Avenue, Suite 490-204
 *                     Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright    Copyright (c) 2006, Cake Software Foundation, Inc.
 * @link         http://www.cakefoundation.org/projects/info/cakephp CakePHP Project
 * @package      cake
 * @subpackage   cake.cake.libs.controller.components.dbacl.models
 * @since        CakePHP v 0.10.0.1232
 * @version      $Revision$
 * @modifiedby   $LastChangedBy$
 * @lastmodified $Date$
 * @license      http://www.opensource.org/licenses/mit-license.php The MIT License
 */


/**
 * Short description for file.
 *
 * Long description for file
 *
 * @package    cake
 * @subpackage cake.cake.libs.controller.components.dbacl.models
 * @since      CakePHP v 0.10.0.1232
 *
 */
class AclNode extends AppModel
{

    var $cacheQueries = false;

/**
 * Enter description here...
 *
 * @param unknown_type $link_id
 * @param unknown_type $parent_id
 * @param unknown_type $alias
 * @return unknown
 */
    function create($link_id = 0, $parent_id = null, $alias = '')
    {
      parent::create();

      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return null;
      }
      extract($this->__dataVars());

      if($parent_id == null || $parent_id === 0)
      {
         $parent = $this->find(null, "MAX(rght)", null, null, 0);
         $parent['lft'] = $parent[0]['MAX(rght)'];

         if($parent[0]['MAX(rght)'] == null)
         {
// The tree is empty
            $parent['lft'] = 0;
         }
      }
      else
      {
         $parent = $this->find($this->_resolveID($parent_id), null, null, 0);
         if($parent == null || count($parent) == 0)
         {
            trigger_error("Null parent in {$class}::create()", E_USER_WARNING);
            return null;
         }

         $parent = $parent[$class];
         $this->_syncTable($table_name, 1, $parent['lft'], $parent['lft']);
      }

      $return = $this->save(array($class => array(
        $secondary_id => $link_id,
        'alias'        => $alias,
        'lft'         => $parent['lft'] + 1,
        'rght'        => $parent['lft'] + 2
      )));

      $this->setId($this->getLastInsertID());
      return $return;
    }


/**
 * Enter description here...
 *
 * @param unknown_type $parent_id
 * @param unknown_type $id
 * @return unknown
 */
    function setParent($parent_id = null, $id = null)
    {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return null;
      }
      extract($this->__dataVars());

      if($id == null && $this->id == false)
      {
         return false;
      }
      else if($id == null)
      {
         $id = $this->id;
      }

      $object = $this->find($this->_resolveID($id), null, null, 0);
      if($object == null || count($object) == 0)
      {
         // Couldn't find object
         return false;
      }
      $object = $object[$class];
      $parent = $this->getParent($id);

      // Node is already at root, or new parent == old parent
      if(($parent == null && $parent_id == null) || ($parent_id == $parent[$class][$secondary_id] && $parent_id != null) || ($parent_id == $parent[$class]['alias'] && $parent_id != null))
      {
         return false;
      }

      if($parent_id == null)
      {
         $newParent = $this->find(null, "MAX(rght) as lft", null, 0);
         $newParent = $newParent[0];
         $newParent['rght'] = $newParent['lft'];
      }
      else
      {
         $newParent = $this->find($this->_resolveID($parent_id), null, null, 0);
         $newParent = $newParent[$class];
      }

      if($parent_id != null && $newParent['lft'] <= $object['lft'] && $newParent['rght'] >= $object['rght'])
      {
         // Can't move object inside self or own child
         return false;
      }

      $this->_syncTable($table_name, 0, $object['lft'], $object['lft']);
      
      if ($object['lft'] < $newParent['lft'])
      {
          $newParent['lft']  = $newParent['lft']  - 2;
          $newParent['rght'] = $newParent['rght'] - 2;
      }

      if ($parent_id != null)
      {
          $this->_syncTable($table_name, 1, $newParent['lft'], $newParent['lft']);
      }

      $object['lft']  = $newParent['lft'] + 1;
      $object['rght'] = $newParent['lft'] + 2;
      $this->save(array($class => $object));

      if($newParent['lft'] == 0)
      {
         $this->_syncTable($table_name, 2, $newParent['lft'], $newParent['lft']);
      }
      return true;
    }


/**
 * Get the parent node of the given Aro or Aco
 *
 * @param moxed $id
 * @return array
 */
    function getParent($id)
    {
      $path = $this->getPath($id);
      if($path == null || count($path) < 2)
      {
         return null;
      }
      else
      {
         return $path[count($path) - 2];
      }
    }

/**
 * Gets the path to the given Aro or Aco
 *
 * @param mixed $id
 * @return array
 */
    function getPath($id)
    {
      if (strtolower(get_class($this)) == "aclnode")
      {
         trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
         return null;
      }
      extract($this->__dataVars());

      $item = $this->find($this->_resolveID($id), null, null, 0);
      if($item == null || count($item) == 0)
      {
         return null;
      }
      return $this->findAll(array($class.'.lft' => '<= '.$item[$class]['lft'], $class.'.rght' => '>= '.$item[$class]['rght']), null, array($class.'.lft' => 'ASC'), null, null, 0);
    }

/**
 * Get the child nodes of the given Aro or Aco
 *
 * @param mixed $id
 * @return array
 */
    function getChildren($id)
    {
        if (strtolower(get_class($this)) == "aclnode")
        {
           trigger_error(__("[acl_base] The AclBase class constructor has been called, or the class was instantiated. This class must remain abstract. Please refer to the Cake docs for ACL configuration."), E_USER_ERROR);
           return null;
        }
        extract($this->__dataVars());

        $item = $this->find($this->_resolveID($id), null, null, 0);
        return $this->findAll(array($class.'.lft' => '> '.$item[$class]['lft'], $class.'.rght' => '< '.$item[$class]['rght']), null, null, null, null, null, 0);
    }

/**
 * Gets a conditions array to find an Aro or Aco, based on the given id or alias
 *
 * @param mixed $id
 * @return array Conditions array for a find/findAll call
 */
    function _resolveID($id)
    {
        extract($this->__dataVars());
        $key = (is_numeric($id) ? $secondary_id : 'alias');
        return array($this->name.'.'.$key => $id);
    }

/**
 * Shifts the left and right values of the aro/aco tables
 * when a node is added or removed
 *
 * @param unknown_type $table
 * @param unknown_type $dir
 * @param unknown_type $lft
 * @param unknown_type $rght
 */
    function _syncTable($table, $dir, $lft, $rght)
    {
        $db =& ConnectionManager::getDataSource($this->useDbConfig);

        if ($dir == 2)
        {
            $shift = 1;
            $dir = '+';
        }
        else
        {
            $shift = 2;
            if ($dir > 0)
            {
                $dir = '+';
            }
            else
            {
                $dir = '-';
            }
        }

        $db->query('UPDATE '.$table.' SET rght = rght '.$dir.' '.$shift.' WHERE rght > '.$rght);
        $db->query('UPDATE '.$table.' SET lft  = lft  '.$dir.' '.$shift.' WHERE lft  > '.$lft);
    }

/**
 * Enter description here...
 *
 * @return unknown
 */
    function __dataVars()
    {
      $vars = array();
      $class = strtolower(get_class($this));
      if ($class == 'aro')
      {
          $vars['secondary_id'] = 'user_id';
      }
      else
      {
          $vars['secondary_id'] = 'object_id';
      }
      $vars['table_name']    = $class . 's';
      $vars['class']        = ucwords($class);
      return $vars;
    }
}

?>