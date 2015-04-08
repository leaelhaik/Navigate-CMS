<?php
require_once(NAVIGATE_PATH.'/lib/packages/templates/template.class.php');
require_once(NAVIGATE_PATH.'/lib/packages/webdictionary/webdictionary.class.php');
require_once(NAVIGATE_PATH.'/lib/packages/webdictionary/webdictionary_history.class.php');
require_once(NAVIGATE_PATH.'/lib/packages/paths/path.class.php');
require_once(NAVIGATE_PATH.'/lib/packages/webuser_votes/webuser_vote.class.php');

class item
{
	public $id;
	public $association;
	public $category;
	public $embedding;
	public $template;
    public $date_to_display;
	public $date_published;
	public $date_unpublish;
	public $galleries;
	public $date_created;
	public $date_modified;
	public $comments_enabled_to; // 0 => nobody, 1=>registered, 2=>everyone
	public $comments_moderator; // user_id
	public $access; // 0 => everyone, 1 => logged in, 2 => not logged in, 3 => selected webuser groups
    public $groups;
	public $permission; // 0 => public, 1 => private (only navigate cms users), 2 => hidden
	public $views;
	public $author;
	public $votes;
	public $score;

    public $dictionary;
    public $paths;
	public $properties;
		
	public function load($id)
	{
		global $DB;
		global $website;
		
		if($DB->query('SELECT * FROM nv_items 
						WHERE id = '.intval($id).'
						  AND website = '.$website->id))
		{
			$data = $DB->result();
			$this->load_from_resultset($data); // there will be as many entries as languages enabled
		}
	}
	
	public function load_random($categories=array())
	{
		global $DB;
		global $website;
				
		if($DB->query('SELECT * FROM nv_items 
						WHERE category IN('.implode(',', $categories).')
						  AND website = '.$website->id.'
						ORDER BY RAND()
						LIMIT 1'))
		{
			$data = $DB->result();
			$this->load_from_resultset($data); // there will be as many entries as languages enabled
		}			
	}
	
	public function load_from_resultset($rs)
	{
		$main = $rs[0];
		
		$this->id				= $main->id;
		$this->association 		= $main->association;
		$this->category			= $main->category;
		$this->embedding		= $main->embedding;		
		$this->template			= $main->template;
		$this->date_to_display	= (empty($main->date_to_display)? '' : $main->date_to_display);
		$this->date_published	= (empty($main->date_published)? '' : $main->date_published);
		$this->date_unpublish	= (empty($main->date_unpublish)? '' : $main->date_unpublish);
		$this->date_created		= $main->date_created;
		$this->date_modified	= $main->date_modified;		
		$this->galleries		= mb_unserialize($main->galleries);
		$this->comments_enabled_to = $main->comments_enabled_to;
		$this->comments_moderator = $main->comments_moderator;
		$this->access			= $main->access;	
		$this->permission		= $main->permission;		
		$this->author			= $main->author;
		$this->views			= $main->views;
		$this->votes			= $main->votes;
		$this->score			= $main->score;

		$this->dictionary		= webdictionary::load_element_strings('item', $this->id);
		$this->paths			= path::loadElementPaths('item', $this->id);

        // to get the array of groups first we remove the "g" character
        // to get the array of groups first we remove the "g" character
        $groups = str_replace('g', '', $main->groups);
        $this->groups = explode(',', $groups);
        if(!is_array($this->groups))  $this->groups = array($groups);

        if($this->association == 'free')
            $this->category = '';
    }
	
	public function load_from_post()
	{
		global $website;

		$this->association		= $_REQUEST['association'][0];
		$this->category			= intval($_REQUEST['category']);
		$this->embedding		= ($_REQUEST['embedding'][0]=='1')? '1' : '0';
		$this->template			= ($this->embedding=='0' || $this->association=='free')? $_REQUEST['template'] : '';
		$this->author			= intval($_REQUEST['item-author']);
		
		$this->date_to_display	= (empty($_REQUEST['date_to_display'])? '' : core_date2ts($_REQUEST['date_to_display']));
		$this->date_published	= (empty($_REQUEST['date_published'])? '' : core_date2ts($_REQUEST['date_published']));
		$this->date_unpublish	= (empty($_REQUEST['date_unpublish'])? '' : core_date2ts($_REQUEST['date_unpublish']));
		$this->access			= intval($_REQUEST['access']);

        $this->groups	        = $_REQUEST['groups'];
        if($this->access < 3)
            $this->groups = array();

		$this->permission		= intval($_REQUEST['permission']);	

        // if comment settings were not visible, keep the original values
        if(isset($_REQUEST['item-comments_enabled_to']))
        {
            $this->comments_enabled_to 	= intval($_REQUEST['item-comments_enabled_to']);
            $this->comments_moderator 	= intval($_REQUEST['item-comments_moderator']);
            if(empty($_REQUEST['item-comments_moderator-text']))
                $this->comments_moderator   =   0;
        }

		// language strings and options
		$this->dictionary = array();
		$this->paths = array();

		$template = $this->load_template();

		//$fields = array('title', 'body');
		$fields = array('title', 'tags');
		
		if(!is_array($template->sections))
            $template->sections = array('code' => 'main');
		
		foreach($template->sections as $section)
		{			
			if(is_object($section)) 
				$section = (array) $section;

			if(is_array($section))
				$section = $section['code'];
			
			$fields[] = 'section-'.$section;	
		}
		
		foreach($_REQUEST as $key => $value)
		{
			if(empty($value)) continue;
			
			foreach($fields as $field)
			{				
				if(substr($key, 0, strlen($field.'-'))==$field.'-')
					$this->dictionary[substr($key, strlen($field.'-'))][$field] = $value;
			}

			if(substr($key, 0, strlen('path-'))=='path-')
            {
                // set a path only if "Free" or "Category/Own Path" associations
                if( $this->association=='free' ||
                    ($this->association=='category' && $this->embedding==0) )
                {
				    $this->paths[substr($key, strlen('path-'))] = $value;
                }
            }
		}

		// image galleries
		$this->galleries = array();
		
		$items = explode("#", $_REQUEST['items-gallery-elements-order']);
		if(!is_array($items)) $items = array();
		$gallery_items = array();
		$gallery_items[0] = array();
		if(!is_array($website->languages)) $website->languages = array();
		foreach($items as $item)
		{
			if(empty($item)) continue;
			
			// capture image captions
			$gallery_items[0][$item] = array();
			
			foreach($website->languages_list as $lang)
			{
				$gallery_items[0][$item][$lang] = $_REQUEST['items-gallery-item-'.$item.'-dictionary-'.$lang];
			}
		}
		
		$this->galleries = $gallery_items;
		// galleries[0] = array( [id-file] => array(es => '', ca => '',.. ), [id-file-2] => array... )
	}
	
	
	public function save()
	{
		if(!empty($this->id))
			return $this->update();
		else
			return $this->insert();			
	}
	
	public function delete()
	{
		global $DB;
		global $website;

		if(!empty($this->id))
		{
			// remove dictionary elements
			webdictionary::save_element_strings('item', $this->id, array());
			
			// remove path elements
			path::saveElementPaths('item', $this->id, array());
			
			// remove all votes assigned to element
			webuser_vote::remove_object_votes('item', $this->id);

            // remove all element properties
            property::remove_properties('item', $this->id);

            // finally remove the item
			$DB->execute('DELETE FROM nv_items
								WHERE id = '.intval($this->id).'
								  AND website = '.$website->id
						);
		}
		
		return $DB->get_affected_rows();		
	}
	
	public function insert()
	{
		global $DB;
		global $website;
		
		$this->date_created  = core_time();		
		$this->date_modified = core_time();

        if(empty($this->comments_enabled_to))
        {
            // apply default comment settings from website properties
            $this->comments_enabled_to = $website->comments_enabled_for;
            $this->comments_moderator = $website->comments_default_moderator;
            if($this->comments_moderator == 'c_author')
                $this->comments_moderator = $this->author;
        }

        $groups = '';
        if(is_array($this->groups))
        {
            $this->groups = array_unique($this->groups); // remove duplicates
            $this->groups = array_filter($this->groups); // remove empty
            if(!empty($this->groups))
                $groups = 'g'.implode(',g', $this->groups);
        }

        if($groups == 'g')
            $groups = '';

        $ok = $DB->execute(' INSERT INTO nv_items
								(id, website, association, category, embedding, template, 
								 date_to_display, date_published, date_unpublish, date_created, date_modified, author,
								 galleries, comments_enabled_to, comments_moderator, 
								 access, groups, permission,
								 views, votes, score)
								VALUES 
								( 0,
								  '.$website->id.',
								  '.protect($this->association).',
								  '.protect($this->category).',
								  '.protect($this->embedding).',
								  '.protect($this->template).',
								  '.protect($this->date_to_display).',
								  '.protect($this->date_published).',
								  '.protect($this->date_unpublish).',
								  '.protect($this->date_created).',
								  '.protect($this->date_modified).',
								  '.protect($this->author).',								  								  
								  '.protect(serialize($this->galleries)).',
								  '.protect($this->comments_enabled_to).',
								  '.protect($this->comments_moderator).',
								  '.protect($this->access).',
								  '.protect($groups).',
								  '.protect($this->permission).',
  								  0,
								  0,
								  0
								)');						
			
		if(!$ok) throw new Exception($DB->get_last_error());
		
		$this->id = $DB->get_last_id();
				
		webdictionary::save_element_strings('item', $this->id, $this->dictionary);
		webdictionary_history::save_element_strings('item', $this->id, $this->dictionary);
   		path::saveElementPaths('item', $this->id, $this->paths);		
		
		return true;
	}
	
	public function update()
	{
		global $DB;
		global $website;
			
		$this->date_modified = core_time();

        $groups = '';
        if(is_array($this->groups))
        {
            $this->groups = array_unique($this->groups); // remove duplicates
            $this->groups = array_filter($this->groups); // remove empty
            if(!empty($this->groups))
                $groups = 'g'.implode(',g', $this->groups);
        }

        if($groups == 'g')
            $groups = '';

        $ok = $DB->execute(' UPDATE nv_items
								SET 
									association	= '.protect($this->association).',
									category	=   '.protect($this->category).',
									embedding	= 	'.protect($this->embedding).',
									template	=   '.protect($this->template).',
									date_to_display	=   '.protect($this->date_to_display).',
									date_published	=   '.protect($this->date_published).',
									date_unpublish	=   '.protect($this->date_unpublish).',
									date_modified	=   '.protect($this->date_modified).',
									author		=   '.protect($this->author).',									
									galleries	=  '.protect(serialize($this->galleries)).',
									comments_enabled_to = '.protect($this->comments_enabled_to).',
									comments_moderator = '.protect($this->comments_moderator).',
									access	 	=  '.protect($this->access).',
									groups      =  '.protect($groups).',
									permission 	=  '.protect($this->permission).',
									views 	=  '.protect($this->views).',
									votes 	=  '.protect($this->votes).',
									score 	=  '.protect($this->score).'
							WHERE id = '.$this->id.'
							  AND website = '.$website->id);
		
		if(!$ok) throw new Exception($DB->get_last_error());

		webdictionary::save_element_strings('item', $this->id, $this->dictionary);
		webdictionary_history::save_element_strings('item', $this->id, $this->dictionary);
   		path::saveElementPaths('item', $this->id, $this->paths);		
				
		return true;
	}

	public function load_template()
	{
		global $DB;
		global $website;
		
		$template = new template();	
		
		if(	$this->association == 'free' ||
			($this->association == 'category' && $this->embedding == '0'))
		{
			$template->load($this->template);	
		}
		else
		{
			$category_template = $DB->query_single(
                'template',
                'nv_structure',
                ' id = '.protect($this->category).' AND website = '.$website->id
            );
			$template->load($category_template);
		}
		
		return $template;
	}
	
	public function property($property_name, $raw=false)
	{
        global $DB;

		// load properties if not already done
		if(empty($this->properties))
        {
            // check if this is an embedded item or it is a free element
            if($this->embedding == 1 && $this->association == 'category')
            {
                // properties are given in structure definition
                $structure_template = @$DB->query_single('template', 'nv_structure', 'id = '.intval($this->category));
                $this->properties = property::load_properties('structure', $structure_template, 'item', $this->id);
            }
            else
            {
			    $this->properties = property::load_properties('item', $this->template, 'item', $this->id);
            }
        }
		
		for($p=0; $p < count($this->properties); $p++)
		{
			if($this->properties[$p]->name==$property_name || $this->properties[$p]->id==$property_name)
			{
				if($raw)
					$out = $this->properties[$p]->value;
				else
					$out = $this->properties[$p]->value;
					
				break;
			}
		}
		
		return $out;
	}

    public function property_definition($property_name)
	{
        global $DB;

		// load properties if not already done
		if(empty($this->properties))
        {
            // check if this is an embedded item or it is a free element
            if($this->embedding == 1 && $this->association == 'category')
            {
                // properties are given in structure definition
                $structure_template = @$DB->query_single('template', 'nv_structure', 'id = '.intval($this->category));
                $this->properties = property::load_properties('structure', $structure_template, 'item', $this->id);
            }
            else
            {
			    $this->properties = property::load_properties('item', $this->template, 'item', $this->id);
            }
        }

		for($p=0; $p < count($this->properties); $p++)
		{
			if($this->properties[$p]->name==$property_name || $this->properties[$p]->id==$property_name)
			{
                $out = $this->properties[$p];
                break;
			}
		}

		return $out;
	}

    public function link($lang)
    {
        $url = $this->paths[$lang];
        $url = nvweb_prepare_link($url);
        return $url;
    }

    public static function convert_from_rss($articles = array())
    {
        global $website;

        $items = array();

        if(!is_array($articles))
            $articles = array();

        foreach($articles as $key => $article)
        {
            $item = new item();
            $item->id = 'rss-'.$key;
            $item->association = 'free';
            $item->category = 0;
            $item->embedding = 0;
            $item->template = 0;
            $item->date_to_display = $article['timestamp'];
            $item->date_published = 0;
            $item->date_unpublish = 0;
            $item->galleries = 0;
            $item->date_created = $article['timestamp'];
            $item->data_modified = $article['timestamp'];
            $item->comments_enabled_to = 2; // 0 => nobody, 1=>registered, 2=>everyone
            $item->comments_moderator = 0; // user_id
            $item->access = 0; // 0 => everyone, 1 => registered and logged in, 2 => not registered or not logged in
            $item->groups = array();
            $item->permission = 0; // 0 => public, 1 => private (only navigate cms users), 2 => hidden
            $item->views = 0;
            $item->author = $article['creator'];
            $item->votes = 0;
            $item->score = 0;
            $item->properties = array();
            $item->dictionary = array();
            $item->paths = array();

            foreach($website->languages_list as $wlang)
            {
                $item->dictionary[$wlang] = array(
                    'title' => $article['title'],
                    'section-main' => $article['description']
                );

                $item->paths[$wlang] = $article['link'];
            }

            $items[] = $item;
        }

        return $items;
    }

    public static function reorder($order)
    {
        global $DB;
		global $website;

		$items = explode("#", $order);

		for($i=0; $i < count($items); $i++)
		{
			if(empty($items[$i])) continue;

			$ok =	$DB->execute('UPDATE nv_items
									 SET position = '.($i+1).'
								   WHERE id = '.$items[$i].'
						 		     AND website = '.$website->id);

			if(!$ok) return array("error" => $DB->get_last_error());
		}

		return true;
    }
	
	public function quicksearch($text)
	{
		global $DB;
		global $website;
		
		$like = ' LIKE '.protect('%'.$text.'%');
		
		// we search for the IDs at the dictionary NOW (to avoid inefficient requests)
		
		$DB->query('SELECT DISTINCT (nvw.node_id)
					 FROM nv_webdictionary nvw
					 WHERE nvw.node_type = "item" 
					   AND nvw.website = '.$website->id.' 
					   AND nvw.text '.$like, 'array');
						   
		$dict_ids = $DB->result("node_id");
		
		// all columns to look for	
		$cols[] = 'i.id' . $like;
		
		/* INEFFICIENT WAY
		$cols[] = 'i.id IN ( SELECT nvw.node_id 
							 FROM nv_webdictionary nvw
							 WHERE nvw.node_type = "item" AND
								   nvw.text '.$like.'
							)' ;
		*/
		if(!empty($dict_ids))
			$cols[] = 'i.id IN ('.implode(',', $dict_ids).')';
			
		$where = ' AND ( ';	
		$where.= implode( ' OR ', $cols); 
		$where .= ')';
		
		return $where;
	}

    public function backup($type='json')
    {
        global $DB;
        global $website;

        $out = array();

        $DB->query('SELECT * FROM nv_items WHERE website = '.protect($website->id), 'object');

        if($type='json')
            $out = json_encode($DB->result());

        return $out;
    }
}

?>