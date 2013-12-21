<?php
/**
 * @package      CrowdFunding
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;

jimport('joomla.application.categories');

// Load router
if(!class_exists("CrowdFundingHelperRoute")) {
    $helperDir = JPATH_SITE . DIRECTORY_SEPARATOR. "components" . DIRECTORY_SEPARATOR ."com_crowdfunding". DIRECTORY_SEPARATOR . "helpers";
    JLoader::register("CrowdFundingHelperRoute", $helperDir . DIRECTORY_SEPARATOR . "route.php");
}

/**
 * Method to build Route
 * @param array $query
 */
function CrowdFundingBuildRoute(&$query){
    
    $segments = array();
    
    // get a menu item based on Itemid or currently active
    $app  = JFactory::getApplication();
    $menu = $app->getMenu();
    
    // we need a menu item.  Either the one specified in the query, or the current active one if none specified
    if(empty($query['Itemid'])){
        $menuItem = $menu->getActive();
    }else{
        $menuItem = $menu->getItem($query['Itemid']);
    }
    
    $mOption	= (empty($menuItem->query['option'])) ? null : $menuItem->query['option'];
    $mView	    = (empty($menuItem->query['view']))   ? null : $menuItem->query['view'];
	$mCatid	    = (empty($menuItem->query['catid']))  ? null : $menuItem->query['catid'];
	$mId	    = (empty($menuItem->query['id']))     ? null : $menuItem->query['id'];
	
	// If is set view && Itemid missing, we have to put the view to the segments
	if (isset($query['view'])) {
		$view = $query['view'];
		
		if (empty($query['Itemid']) OR ($mOption !== "com_crowdfunding")) {
			$segments[] = $query['view'];
		}

		// We need to keep the view for forms since they never have their own menu item
		if ($view != 'form') {
			unset($query['view']);
		}
	};
	
    // are we dealing with a category that is attached to a menu item?
	if (isset($view) && ($mView == $view) && (isset($query['id'])) && ($mId == intval($query['id']))) {
		unset($query['view']);
		unset($query['catid']);
		unset($query['id']);
		return $segments;
	}
	
    // Views
	if(isset($view)) {
	    
    	switch($view) {
    	    
    	    case "backing":
    	        
    	        $catId      = $query['catid'];
    	        CrowdFundingHelperRoute::prepareCategoriesSegments($catId, $segments, $mId);
    	        
    	        $id = $query['id'];
				$segments[] = $id;
				
				unset($query['id']);
	            unset($query['catid']);
    	        
    	        $segments[] = "backing";
    	        
    	        break;
    	        
	       case "details":
	            
    	        $catId      = $query['catid'];
    	        CrowdFundingHelperRoute::prepareCategoriesSegments($catId, $segments, $mId);
    	        
    	        $id = $query['id'];
				$segments[] = $id;
				
				unset($query['id']);
	            unset($query['catid']);
    	        break;
    	        
	        case "embed":
	            
    	        $catId = $query['catid'];
    	        CrowdFundingHelperRoute::prepareCategoriesSegments($catId, $segments, $mId);
    	        unset($query['catid']);
    	        
    	        $id = $query['id'];
				$segments[] = $id;
				unset($query['id']);
	            
	            $segments[] = "embed";
	            
    	        break;
    	        
	        case "project": // Form for adding prajects
	            
	            if($menuItem->query["view"] == $view) {
	                unset($query['view']);
	            }
	            
	            break;
    	}
        
	}
    
	// Layout
    if (isset($query['layout'])) {
		if (!empty($query['Itemid']) && isset($menuItem->query['layout'])) {
			if ($query['layout'] == $menuItem->query['layout']) {
				unset($query['layout']);
			}
		} else {
			if ($query['layout'] == 'default') {
				unset($query['layout']);
			}
		}
	};
	
	// Screen
	if(isset($query["screen"])) {
	    $segments[] = $query["screen"];
	    unset($query['screen']);
	}
	
    return $segments;
}

/**
 * Method to parse Route
 * @param array $segments
 */
function CrowdFundingParseRoute($segments){
    
    $vars = array();
    
    //Get the active menu item.
    $app        = JFactory::getApplication();
    $menu       = $app->getMenu();
    $item       = $menu->getActive();
    
    // Count route segments
    $count      = count($segments);
    
    // Standard routing for articles.  If we don't pick up an Itemid then we get the view from the segments
	// the first segment is the view && the last segment is the id of the details, category or payment.
    if(!isset($item)) {
        $vars['view']   = $segments[0];
        $vars['catid']  = $segments[$count - 1];
        return $vars;
    } 
    
    
    // COUNT == 1
    
    // Category ( Discover )
	if($count == 1) { 
	    
	    // we check to see if an alias is given.  If not, we assume it is a project,
	    // because categories have always alias.
	    if (false == strpos($segments[0], ':')) {
	        $vars['view'] = 'details';
	        $vars['id']   = (int)$segments[0];
	        return $vars;
	    }
	    
	    list($id, $alias) = explode(':', $segments[0], 2);
	    
	    // first we check if it is a category
	    $category = JCategories::getInstance('CrowdFunding')->get($id);

	    if ($category && ( strcmp($category->alias, $alias) == 0) ) {
	        $vars['view'] = 'discover';
	        $vars['id']   = $id;
	        return $vars;
	    } else {
	        $project = CrowdFundingHelperRoute::getProject($id);
			if ($project) {
				if ($project->alias == $alias) {
					$vars['view']  = 'details';
					$vars['catid'] = (int)$project->catid;
					$vars['id']    = (int)$id;

					return $vars;
				}
			}
		}
	    
	}
	
	// COUNT >= 2
	
	if($count >= 2) { 
	    
	    $view  = $segments[$count - 1];
	    
	    switch($view) {
	        
	        case "backing":
	            
	            $itemId         = (int)$segments[$count - 2];
	            
	            // Get catid from menu item
	            if(!empty($item->query["id"])) {
	                $catId    = (int)$item->query["id"];
	            } else {
	                $catId    = (int)$segments[$count - 3];
	            }
	            
        	    $vars['view']   = 'backing';
        		$vars['id']     = (int)$itemId;
        		$vars['catid']  = (int)$catId;
        		
	            break;
	            
            case "embed": // Backing without reward
	            
                $itemId         = (int)$segments[$count - 2];
                
                // Get catid from menu item
                if(!empty($item->query["id"])) {
                    $catId    = (int)$item->query["id"];
                } else {
                    $catId    = (int)$segments[$count - 3];
                }
                
        	    $vars['view']   = 'embed';
        		$vars['id']     = (int)$itemId;
        		$vars['catid']  = (int)$catId;
        		
	            break;
	            
            case "updates": // Screens of details - "updates", "comments", "funders"
	        case "comments":
            case "funders":    
                
	            $itemId         = (int)$segments[$count - 2];
	            
	            // Get catid from menu item
	            if(!empty($item->query["id"])) {
	                $catId    = (int)$item->query["id"];
	            } else {
	                $catId    = (int)$segments[$count - 3];
	            }
	            
        	    $vars['view']   = 'details';
        		$vars['id']     = (int)$itemId;
        		$vars['catid']  = (int)$catId;
        		
        		// Get screen
        		$screen    = $segments[$count - 1];
        	    $allowedScreens = array("updates", "comments", "funders");
        	    if(in_array($screen, $allowedScreens)) {
        	        $vars['screen'] = $screen;
        	    }
        	    
	            break;
	            
	        default:
	            
        	    // if there was more than one segment, then we can determine where the URL points to
            	// because the first segment will have the target category id prepended to it.  If the
            	// last segment has a number prepended, it is details, otherwise, it is a category.
            	$catId = (int)$segments[$count - 2];
            	$id    = (int)$segments[$count - 1];
            
            	if ($id > 0 && $catId > 0) {
            		$vars['view']   = 'details';
            		$vars['catid']  = $catId;
            		$vars['id']     = $id;
            	} else {
            		$vars['view']   = 'category';
            		$vars['id']     = $id;
            	}
            	
            	break;
    	
	    }
	    
	}
	
    return $vars;
}
