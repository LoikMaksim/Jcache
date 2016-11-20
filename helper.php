<?php

// no direct access
defined('_JEXEC') or die('Direct access not allowed');

class  CAT_PROD_SLIDER
{
	protected $doc;
	protected $_db;
	protected $_param;

   function InitModule($params)
    {
		$this->doc	=  JFactory::getDocument();
		$this->_db  = JFactory::getDBO();
		$this->_param = $params;
    }

	public function getList()
	{
		$start = microtime(1); 

		$categs = $this->_param->get('categories');
		$type   = $this->_param->get('type');


		$query = $this->_db->getQuery(true);

		$prod = array();
		$cache = new JCache(array('caching'=> 1,'lifetime' => 3600,'locktime' => '400'));
		$cache_name = 'slider_prod_cat_'.implode(',',$categs);

		$storder_cache = $cache->get($cache_name,'cat_slider');

		if(empty($storder_cache))
		{
			$query->select('SQL_CALC_FOUND_ROWS  p.id')->from('#__ksenmart_products AS p');
			$query->leftjoin('#__ksenmart_products_categories AS c ON c.product_id=p.id');
				
			if(!empty($categs))
			{
				$query->where('c.category_id IN ('.implode(',',$categs).')');
			}	
				
			$query->where('p.published = 1');

			$query->order('p.ordering ASC')->where('(p.parent_id = 0)');;

				  
			if ($type == 'hot') 
			{
				$query->where('(p.hot = 1)');
			}

			if ($type == 'new') 
			{
				$query->where('(p.new = 1)');
			}

			if ($type == 'recommendation') 
			{
				$query->where('(p.recommendation = 1)');
			}

			if ($type == 'promotion') 
			{
				$query->where('(p.promotion = 1)');
			}


			$this->_db->setQuery($query, 0, $this->_param->get('max_items', 10));
			$list = $this->_db->loadObjectList();
		
		

			if(!empty($list))
			{
				$prod = $this->getProds($list);	
				$cache->store(serialize($prod),$cache_name,'cat_slider');
			}
			
		}

		else
		{
			$prod = unserialize($storder_cache);
		}

		if(!empty($prod))
		{
			foreach ($prod as &$it)
			{
				if(!empty($it->date_to))
				{
				    $it->date_to = date('Y-m-d',strtotime($it->date_to));
				}
			}
		}
		//KSSystem::dump($prod);

		$finish = microtime(1); 
		$totaltime = $finish - $start; 
	//	echo 'Слайдер Продуктво выполнения: '.$totaltime; 
		
		return $prod;
	}

	 public function getProds($products) 
	 {
    	    foreach ($products as &$product) 
	    {
                $product= KSMProducts::getProduct($product->id);
	        $product->slider_image = KSMedia::resizeImage($product->filename, $product->folder, 200, 150,array(),null);
    	    }

        return $products;
    }
}