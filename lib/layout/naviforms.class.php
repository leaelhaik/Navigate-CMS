<?php
require_once(NAVIGATE_PATH.'/lib/packages/files/file.class.php');

class naviforms
{
	public function select_from_object_array($id, $data, $value_field, $title_field, $selected_value="", $style="", $remove_keys=array(), $control_replacement=true)
	{
		$out = array();

        $class = '';
        if($control_replacement)
        {
            $class = 'select2';
        }

		$out[] = '<select class="'.$class.'" name="'.$id.'" id="'.$id.'" style="'.$style.'">';
				
		if(!is_array($data)) $data = array();
		
		foreach($data as $row)
        {
			if(in_array($row->{$value_field}, $remove_keys)) continue;
			if($row->{$value_field}==$selected_value)
				$out[] = '<option value="'.$row->{$value_field}.'" selected="selected">'.$row->{$title_field}.'</option>';
			else
				$out[] = '<option value="'.$row->{$value_field}.'">'.$row->{$title_field}.'</option>';
		}
		
		$out[] = '</select>';		
		
		return implode("\n", $out);	
	}
	
	public function selectfield($id, $values, $texts, $selected_value="", $onChange="", $multiple=false, $titles=array(), $style="", $control_replacement=true)
	{
        $class = '';
        if($control_replacement)
        {
            $class = 'select2';
        }

		$out = array();
		if($multiple)
			$out[] = '<select name="'.$id.'[]" id="'.$id.'" onchange="'.$onChange.'" multiple="multiple" style=" height: 100px; '.$style.' " >';
		else
			$out[] = '<select class="'.$class.'" name="'.$id.'" id="'.$id.'" onchange="'.$onChange.'" style="'.$style.'">';

		for($i=0; $i < count($values); $i++)
		{
			if( (is_array($selected_value) && in_array($values[$i], $selected_value)) || 
				($values[$i]==$selected_value))
				$out[] = '<option value="'.$values[$i].'" selected="selected" title="'.$titles[$i].'">'.$texts[$i].'</option>';
			else
				$out[] = '<option value="'.$values[$i].'" title="'.$titles[$i].'">'.$texts[$i].'</option>';			
		}
		
		$out[] = '</select>';
		
		return implode("\n", $out);	
	}
	
	public function buttonset($name, $options, $default, $onclick="", $jqueryui_icons=array())
	{
		$buttonset = array();
		$buttonset[] = '<div class="buttonset">';

		foreach($options as $key => $val)
		{
			$buttonset[] = '<input type="radio" id="'.$name.'_'.$key.'" name="'.$name.'[]" value="'.$key.'" '.((!is_null($default) && ($default==$key))? ' checked="checked" ' : '').' />';
            //    $buttonset[] = '<label for="'.$name.'_'.$key.'"  onclick="'.$onclick.'"><span class="ui-button-icon-primary ui-icon '.$icon.'" style=" float: left; "></span> '.$val.'</label>';
			$buttonset[] = '<label class="unselectable" for="'.$name.'_'.$key.'"  onclick="'.$onclick.'">'.$val.'</label>';
		}
		
		$buttonset[] = '</div>';
		
		return implode("\n", $buttonset);		
	}

    public function splitbutton($id, $title, $links, $texts)
	{
        global $layout;

        $out = array();
        $out[] = '<div id="'.$id.'" class="nv-splitbutton" style="float: left;">';
        $out[] =    '<a class="'.$id.'_splitbutton_main" href="'.$links[0].'">'.$title.'</a><a href="#">'.t(200, 'Options').'</a>';
        $out[] = '</div>';
        $out[] = '<ul id="'.$id.'_splitbutton_menu" class="nv_splitbutton_menu" style="display: none; position: absolute; ">';
        for($i=0; $i < count($texts); $i++)
            $out[] = '<li><a href="'.$links[$i].'">'.$texts[$i].'</a></li>';
        $out[] = '</ul>';

        $layout->add_script('
            $(".'.$id.'_splitbutton_main").splitButton();
        ');

		return implode("\n", $out);
	}
	
	public function hidden($name, $value)
	{
		return '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$value.'" />';
	}
	
	public function checkbox($name, $checked=false)
	{
		if($checked)
			$out = '<input id="'.$name.'" name="'.$name.'" type="checkbox" value="1" checked="checked" />';
		else
			$out = '<input id="'.$name.'" name="'.$name.'" type="checkbox" value="1" />';
			
		return $out;
	}
	
	public function textarea($name, $value="", $rows=4, $cols=48, $style="")
	{
        $value = htmlspecialchars($value);
		$out = 	'<textarea name="'.$name.'" id="'.$name.'" rows="'.$rows.'" cols="'.$cols.'" style="'.$style.'">'.$value.'</textarea>';
		return $out;
	}
	
	public function textfield($name, $value="", $width="400px", $action="")
	{
		$value = htmlspecialchars($value);
		$out = '<input type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'" style=" width: '.$width.';" onkeyup="'.$action.'" />';
		return $out;	
	}
	
	public function autocomplete($name, $value="", $url, $callback='""', $width="400px")
	{
		global $layout;
		
		$value = htmlspecialchars($value);
		
		$out = '<input type="text" name="'.$name.'" id="'.$name.'" value="'.$value.'" style=" width: '.$width.';" />';
		
		$layout->add_script('
			$("#'.$name.'").autocomplete(
			{
				source: "'.$url.'",
				minLength: 1,
				select: '.$callback.'
			});
		');
		
		return $out;	
	}	
	
	public function datefield($name, $value="", $hour=false)
	{
		global $layout;
		global $user;

		if(!empty($value))
			$value = core_ts2date($value, $hour);

		$out = '<input type="text" class="datepicker" name="'.$name.'" id="'.$name.'" value="'.$value.'" />
				<img src="img/icons/silk/calendar_delete.png" width="16" height="16" align="absmiddle" 
					 style=" cursor: pointer; " onclick=" $(this).parent().find(\'input\').val(\'\'); " />';
		
		$format = $user->date_format;   // custom user date format

        // format to jquery ui datepicker
        // http://docs.jquery.com/UI/Datepicker/formatDate
        $format = php_date_to_jquery_ui_datepicker_format($format);

        $translations = '
                monthNames: [
                    "'.t(101, "January").'",
                    "'.t(102, "February").'",
                    "'.t(103, "March").'",
                    "'.t(104, "April").'",
                    "'.t(105, "May").'",
                    "'.t(106, "June").'",
                    "'.t(107, "July").'",
                    "'.t(108, "August").'",
                    "'.t(109, "September").'",
                    "'.t(110, "October").'",
                    "'.t(111, "November").'",
                    "'.t(112, "December").'"
                ],
                monthNamesShort: [
                    "'.t(113, "Jan").'",
                    "'.t(114, "Feb").'",
                    "'.t(115, "Mar").'",
                    "'.t(116, "Apr").'",
                    "'.t(117, "May").'",
                    "'.t(118, "Jun").'",
                    "'.t(119, "Jul").'",
                    "'.t(120, "Aug").'",
                    "'.t(121, "Sept").'",
                    "'.t(122, "Oct").'",
                    "'.t(123, "Nov").'",
                    "'.t(124, "Dec").'"
                ],
                dayNames: [
                    "'.t(131, "Sunday").'",
                    "'.t(125, "Monday").'",
                    "'.t(126, "Tuesday").'",
                    "'.t(127, "Wednesday").'",
                    "'.t(128, "Thursday").'",
                    "'.t(129, "Friday").'",
                    "'.t(130, "Saturday").'"
                ],
                dayNamesShort: [
                    "'.t(138, "Sun").'",
                    "'.t(132, "Mon").'",
                    "'.t(133, "Tue").'",
                    "'.t(134, "Wed").'",
                    "'.t(135, "Thu").'",
                    "'.t(136, "Fri").'",
                    "'.t(137, "Sat").'"
                ],
                dayNamesMin: [
                    "'.t(138, "Sun").'",
                    "'.t(132, "Mon").'",
                    "'.t(133, "Tue").'",
                    "'.t(134, "Wed").'",
                    "'.t(135, "Thu").'",
                    "'.t(136, "Fri").'",
                    "'.t(137, "Sat").'"
                ],
                prevText: "'.t(501, "Previous").'",
                nextText: "'.t(502, "Next").'",
                closeText: "'.t(92, "Close").'",
                currentText: "'.t(503, "Now").'",
                timeText: "'.t(504, "Time").'",
                hourText: "'.t(93, "Hour").'",
                minuteText: "'.t(94, "Minute").'",
                secondText: "'.t(96, "Second").'",
        ';

		if(!$hour)
        {
            $format = str_replace('H:i', '', $format);
            $layout->add_script('$("#'.$name.'").datepicker(
            {
                '.$translations.'
                dateFormat: "'.trim($format).'"
            });');
        }
        else
        {
            $format = str_replace('H:i', '', $format);
            $layout->add_script('$("#'.$name.'").datetimepicker(
            {
                '.$translations.'
                dateFormat: "'.trim($format).'",
                timeFormat: "HH:mm"
            });');
        }

		return $out;
	}

    public function colorfield($name, $value="#ffffff")
    {
        global $layout;

        $out = '<input type="text" class="naviforms-colorpicker-text" name="'.$name.'" id="'.$name.'" value="'.$value.'" />
                <div id="'.$name.'-selector" class="naviforms-colorpicker-selector ui-corner-all"><div style="background: '.$value.'; "></div></div>';

		$layout->add_script('$("#'.$name.'").ColorPicker(
		    {
                color: "'.$value.'",
                onShow: function(colpkr)
                {
                    var pos = $("#'.$name.'-selector").offset();
                    $(colpkr).css({left: pos.left + 25, top: pos.top - 3});
                    $(colpkr).fadeIn(500);
                    return false;
                },
                onHide: function(colpkr)
                {
                    $(colpkr).fadeOut(500);
                    return false;
                },
                onChange: function(hsb, hex, rgb)
                {
                    $("#'.$name.'").val("#" + hex);
                    $("#'.$name.'-selector").children().css("backgroundColor", "#" + hex);
                }
            });

            $("#'.$name.'-selector").bind("click", function()
            {
                $("#'.$name.'").ColorPickerShow();
            });

            $("#'.$name.'").bind("change", function()
            {
                $("#'.$name.'").ColorPickerSetColor($(this).val());
                $("#'.$name.'-selector").children().css("backgroundColor", $(this).val());
            });
        ');

        return $out;
    }

	public function scriptarea($name, $value, $syntax="js", $style= " width: 75%; height: 250px; ")
	{
		global $layout;
		global $website;
		global $user;
		
		$out = '<textarea name="'.$name.'" id="'.$name.'" style=" '.$style.' " rows="10">'.$value.'</textarea>';

		$layout->add_script('
			$(window).bind("load", function()
			{
				var cm = CodeMirror.fromTextArea(document.getElementById("'.$name.'"), 
										{
											mode: "text/html", 
											tabMode: "indent",
											lineNumbers: true,
											styleActiveLine: true,
											matchBrackets: true,
											autoCloseTags: true,
                                            extraKeys: {"Ctrl-Space": "autocomplete"}
										});

		        CodeMirror.commands.autocomplete = function(cm) {
                    CodeMirror.showHint(cm, CodeMirror.htmlHint);
                }

				navigate_codemirror_instances.push(cm);
	
				$("#'.$name.'").next().attr("style", "'.$style.'");
				$(".CodeMirror-scroll").css({ width: "100%", height: "100%"});
				
				cm.refresh();
			});
		');

		return $out;
	}
	
	public function editorfield($name, $value, $width="80%", $lang="es")
	{
		global $layout;
		global $website;
		global $user;
        global $theme;

		$height = 400;
		
		$out = '<textarea name="'.$name.'" id="'.$name.'" style=" width: '.$width.'; height: '.$height.'px; ">'.htmlentities($value, ENT_HTML5 | ENT_NOQUOTES, 'UTF-8', true).'</textarea>';

        $content_css = $website->content_stylesheets();

        $layout->add_script('
            $("#'.$name.'").tinymce(
            {
                //script_url : "'.NAVIGATE_URL.'/lib/external/tinymce/tiny_mce.js",
                script_url : "'.NAVIGATE_URL.'/lib/external/tinymce/tiny_mce_gzip.php",
                theme : "advanced",
                skin: "cirkuit",
                plugins : "pre,jqueryinlinepopups,imgmap,style,table,tableDropdown,advimage,advlink,emotions,media,searchreplace,contextmenu,paste,noneditable,visualchars,xhtmlxtras,advlist,spellchecker,loremipsum",
                language: "'.$user->language.'",

                theme_advanced_buttons1 : "formatselect,fontselect,fontsizeselect,|,forecolor,|,backcolor,|,removeformat,visualaid,|,code",
                theme_advanced_buttons2 : "bold,italic,underline,strikethrough|,justifyleft,justifycenter,justifyright,justifyfull,|,outdent,indent,|,bullist,|,sub,sup,|,loremipsum,charmap,|,pre,|,help",
                theme_advanced_buttons3 : "styleselect,|,styleprops,attribs,|,tableDropdown,|,link,unlink,anchor,|,image,imgmap,media,|,spellchecker,|,undo,redo",

                theme_advanced_toolbar_location : "top", // could be external
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_resizing : true,

                handle_event_callback : "navigate_tinymce_event",

                //theme_advanced_fonts : "Andale Mono=\'Andale Mono\';Arial=arial,helvetica,sans-serif;Arial Black=\'Arial Black\';Book Antiqua=\'Book Antiqua\';Century Gothic=\'Century Gothic\';Comic Sans MS=\'Comic Sans MS\';Courier New=\'Courier New\';Georgia=Georgia;Helvetica=Helvetica;Impact=Impact;Symbol=Symbol;Tahoma=Tahoma;Terminal=Terminal;Times News Roman=\'Times News Roman\';Trebuchet MS=\'Trebuchet MS\';Verdana=Verdad;",
                theme_advanced_font_sizes: "8px=8px,9px=9px,10px=10px,11px=11px,12px=12px,13px=13px,14px=14px,15px=15px,16px=16px,17px=17px,18px=18px,20px=20px,24px=24px,26px=26px,28px=28px,30px=30px,32px=32px,36px=36px",

                content_css: "'.$content_css.'",
                valid_elements: "*[*]",
                custom_elements: "nv,code,pre,nvlist,figure,article,nav",
                extended_valid_elements: "nv[*],pre[*],code[*],nvlist[*],figure[*],article[*],nav[*]",
                //encoding: "xml",
                relative_urls: false,
                convert_urls: true,
                remove_script_host: false,
                remove_linebreaks: true,
                disk_cache: true,
                valid_children: "+a[div|p|li],+body[style|script],+code[nv|nvlist]",
                width: ($("#'.$name.'").width()) + "px",
                height: $("#'.$name.'").height() + "px",
                oninit: function()
                {
                    tinyMCE.get("'.$name.'").plugins.spellchecker.selectedLang = "'.$lang.'";

                    $("#'.$name.'").parent().find("iframe").droppable(
                    {
                        drop: function(event, ui)
                        {
                            if(!$(ui.draggable).attr("id")) // not a file!
                            {
                                $("#'.$name.'_tbl").css("opacity", 1);
                                return;
                            }

                            var file_id = $(ui.draggable).attr("id").substring(5);
                            if(!file_id || file_id=="") return;
                            var media = $(ui.draggable).attr("mediatype");
                            var mime = $(ui.draggable).attr("mimetype");
                            var web_id = "'.$website->id.'";
                            navigate_tinymce_add_content($("#'.$name.':tinymce").attr("id"), file_id, media, mime, web_id, ui.draggable);
                            $("#'.$name.'_tbl").css("opacity", 1);
                        },
                        over: function(event, ui)
                        {
                            if(!$(ui.draggable).attr("id")) // not a file!
                                return;

                            $("#'.$name.'_tbl").css("opacity", 0.75);
                        },
                        out: function(event, ui)
                        {
                            $("#'.$name.'_tbl").css("opacity", 1);
                        }
                    });

                    //  $($("#'.$name.'_ifr").contents()[0]).on("scroll", function(e) { navigate_tinymce_scroll(e, "'.$name.'"); });

                    tinymce.get("'.$name.'").dom.events.add(
                        tinymce.get("'.$name.'").dom.doc,
                        "blur",
                        function(e)
                        {
                            navigate_tinymce_event(e, "'.$name.'");
                        }
                    );

                    tinymce.get("'.$name.'").dom.events.add(
                        tinymce.get("'.$name.'").dom.doc,
                        "focus",
                        function(e)
                        {
                            navigate_tinymce_event(e, "'.$name.'");
                        }
                    );

                    tinymce.get("'.$name.'").dom.events.add(
                        tinymce.get("'.$name.'").dom.doc,
                        "scroll",
                        function(e)
                        {
                            navigate_tinymce_event(e, "'.$name.'");
                        }
                    );

                    // restore last known iframe scroll position
                    setTimeout(function()
                    {
                        navigate_tinymce_event({type: "focus"}, "'.$name.'", true);
                    }, 20);
                }
            });
        ');

        /* testing optimal width adjustments width: ($("#'.$name.'").width() - 20) + "px", */
		/* code for "external" toolbar mode, kind of buggy
                handle_event_callback: function(e)
                {
                    if(e.type=="click")
                    {
                        var width = $("textarea#'.$name.'").next().width() - 2;

                        $("#'.$name.'_external.mceExternalToolbar").css({
                            position: "absolute",
                            "margin-left": $("textarea#'.$name.'").prev().width(),
                            width: width,
                            background: $(".mceToolbar").eq(0).css("background-color"),
                            "border-bottom": "solid 1px #ccc"
                        });

                        $("#'.$name.'_external.mceExternalToolbar").find(".mceToolbar.mceLeft").css({
                            width: width,
                            height: "auto",
                            "padding-bottom": "2px"
                        });
                    }
                },

        $layout->add_script('
			$(document).bind("focus", function()
			{
			    $("#'.$name.'_external.mceExternalToolbar").hide();
			});
		');

		*/

		return $out;
	}
	
	public function dropbox($name, $value=0, $media="", $disabled=false)
	{
		global $layout;
		global $website;
		
		$out = array();
		
		$out[] = '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.$value.'" />';		
		
		$out[] = '<div id="'.$name.'-droppable" class="navigate-droppable ui-corner-all">';
				
		if(!empty($value))
		{		
			$f = new file();
			$f->load($value);
						
			if($f->type=='image')
				$out[] = '<img title="'.$f->name.'" src="'.NAVIGATE_DOWNLOAD.'?wid='.$website->id.'&id='.$f->id.'&amp;disposition=inline&amp;width=75&amp;height=75" />';
			else
				$out[] = '<img title="'.$f->name.'" src="'.(navibrowse::mimeIcon($f->mime, $f->type)).'" width="50" height="50" /><br />'.$f->name;
		}
		else
			$out[] = '	<img src="img/icons/misc/dropbox.png" vspace="18" />';
		$out[] = '</div>';
		
		if(!$disabled)
		{
			$out[] = '<div class="navigate-droppable-cancel"><img src="img/icons/silk/cancel.png" /></div>';
			
			$layout->add_script('
				$("#'.$name.'-droppable").next().bind("click", function()
				{
					$("#'.$name.'").val("0");
					$("#'.$name.'-droppable").html(\'<img src="img/icons/misc/dropbox.png" vspace="18" />\');
				});
			');
			
			if(!empty($media))
				$accept = 'accept: ".draggable-'.$media.'",';
							
			$layout->add_script('
				$("#'.$name.'-droppable").droppable(
				{
					'.$accept.'
					hoverClass: "navigate-droppable-hover",
					drop: function(event, ui) 
					{
						var file_id = $(ui.draggable).attr("id").substring(5);
						$("#'.$name.'").val(file_id);
						$(this).html($(ui.draggable).html());
					}
				});
			');	
		}
				
		return implode("\n", $out);
	}

    public function dropdown_tree($id, $tree, $selected_value="", $onChange="eval")
    {
        global $layout;
        global $website;

        $out = array();

        // check available dropdown_tree extensions or just use the default

        $out[] = '<input type="hidden" id="'.$id.'" name="'.$id.'" value="'.$selected_value.'" />';

        $path = "";

        $out[] = '<input type="text" id="tree_path_'.$id.'" value="'.$path.'" readonly="true" />';
        $out[] = '<img src="img/icons/silk/erase.png" width="16" height="16" align="absmiddle"'.
					 'style=" cursor: pointer; " onclick=" tree_wrapper_'.md5($id).'_reset(); " />';

        if(empty($tree))
            $tree = '<ul><li value="0">&nbsp;</li></ul>';

        $out[] = '<div style="float: left;" id="tree_wrapper_'.$id.'">'.$tree.'</div>';

        $layout->add_script('
            $("#tree_wrapper_'.$id.' span").wrap("<a>").css("cursor", "pointer");
            $("#tree_wrapper_'.$id.' ul:first").menu({
                select: function(event, ui)
                {
                    var value = $(ui.item).attr("value");
                    $("#'.$id.'").val(value);
                    tree_wrapper_'.md5($id).'_path(value);
                }
            });
            $("#tree_wrapper_'.$id.' ul:first").css(
                {
                    "position": "absolute",
                    "z-index": 1000,
                    "margin": 1,
                    "width": $("#tree_path_'.$id.'").width()
                }
            ).addClass("navi-ui-widget-shadow").hide();
            $("#tree_wrapper_'.$id.'").find(".ui-menu-icon").css("float", "right");
            $("#tree_path_'.$id.'").on("click", function() {
                setTimeout(function()
                {
                    $("#tree_wrapper_'.$id.' ul:first").fadeIn("fast");
                }, 50);
            });

            function tree_wrapper_'.md5($id).'_reset()
            {
                $("#tree_path_'.$id.'").val("");
                $("#'.$id.'").val(0);
            }

            function tree_wrapper_'.md5($id).'_path(category)
            {
                var path = [];
                var first = $("#tree_wrapper_'.$id.'").find("li[value="+category+"]");

                path.push($(first).find("a:first").text());

                $(first).parentsUntil("div").each(function(i, el)
                {
                    if($(el).is("li"))
                        path.push($(el).find("a:first").text());
                })

                path = path.filter(function(e){return e});
                path = path.reverse();
                path = path.join(" › "); // ╱ ▶

                $("#tree_path_'.$id.'").val(path);

                return path;
            }

            tree_wrapper_'.md5($id).'_path('.$selected_value.');
        ');

        return implode("\n", $out);
    }

    function multiselect($id, $values, $texts, $selected_values=array(), $onChange="", $titles=array(), $style=" height: 200px; width: 742px;")
    {
        global $layout;

        $out = array();

        $out[] = '<select name="'.$id.'[]" id="'.$id.'" multiple="multiple" style=" '.$style.' " >';

        for($i=0; $i < count($values); $i++)
        {
            if( (is_array($selected_values) && in_array($values[$i], $selected_values)) ||
                ($values[$i]==$selected_values))
                $out[] = '<option value="'.$values[$i].'" selected="selected"  title="'.$titles[$i].'">'.$texts[$i].'</option>';
            else
                $out[] = '<option value="'.$values[$i].'"  title="'.$titles[$i].'">'.$texts[$i].'</option>';
        }

        $out[] = '</select>';

        $layout->add_script('
             $.uix.multiselect.i18n["navigatecms"] = {
                itemsSelected: "'.t(510, 'Selected items').': {count}",            // 0, 1
                itemsSelected_plural: "'.t(510, 'Selected items').': {count}",    // n
                //itemsSelected_plural_two: ...                      // 2
                //itemsSelected_plural_few: ...                      // 3, 4
                itemsAvailable: "'.t(511, 'Available items').': {count}",
                itemsAvailable_plural: "'.t(511, 'Available items').': {count}",
                //itemsAvailable_plural_two: ...
                //itemsAvailable_plural_few: ...
                itemsFiltered: "{count}",
                itemsFiltered_plural: "{count}",
                //itemsFiltered_plural_two: ...
                //itemsFiltered_plural_few: ...
                selectAll: "'.t(481, 'Select all').'",
                deselectAll: "'.t(507, 'Deselect all').'",
                search: "'.t(41, "Search").'",
                collapseGroup: "'.t(508, "Collapse").'",
                expandGroup: "'.t(509, "Expand").'",
                selectAllGroup: "'.t(481, 'Select all').'",
                deselectAllGroup: "'.t(507, 'Deselect all').'"
            };
            $("#'.$id.'").multiselect({
                "locale": "navigatecms",
                splitRatio: 0.55,
                sortable: true,
                moveEffect: "fade",
                multiselectChange: function(evt, iu)
                {
                    '.(!empty($onChange)? $onChange.'(evt, ui)' : '').'
                }
            });
        ');

        return implode("\n", $out);
    }
}

?>