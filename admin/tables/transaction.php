<?php
/**
 * @package      CrowdFunding
 * @subpackage   Components
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

defined('_JEXEC') or die;
class CrowdFundingTableTransaction extends JTable {
    
    public function __construct( $db ) {
        parent::__construct( '#__crowdf_transactions', 'id', $db );
    }
    
}