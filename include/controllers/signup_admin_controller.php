<?php

class SignupAdminController extends Controller {

  protected $prerun_hooks = array(
    array('method' => 'checkUser', 'exclusive' => true, 'methodlist' => []),
  );

  /**
   * Show page for editing signup pages
   */
  public function signupPages() {
    $this->page->includeCSS('signup-admin.css');
    $this->page->includeCss('fontello-ebe72605/css/idtemplate.css');

    $this->page->registerEarlyLoadJS('signup/admin/controls.js');
    $this->page->registerEarlyLoadJS('signup/admin/pages/render.js');
    $this->page->registerEarlyLoadJS('signup/admin/pages/controls.js');
    $this->page->registerEarlyLoadJS('signup/admin/toolbar.js');

    $this->page->setTemplate('');
  }

  /**
   * Show page for editing signup settings
   */
  public function signupConfig() {
    $this->page->includeCSS('signup-admin.css');
    $this->page->includeCss('fontello-ebe72605/css/idtemplate.css');

    $this->page->registerEarlyLoadJS('signup/admin/controls.js');
    $this->page->registerEarlyLoadJS('signup/admin/config/render.js');
    $this->page->registerEarlyLoadJS('signup/admin/config/controls.js');
    $this->page->registerEarlyLoadJS('signup/admin/toolbar.js');
    $this->page->registerEarlyLoadJS('signup/admin/config/toolbar_buttons.js');

    $this->page->setTemplate('');
  }

  /**
   * Add element to signup page
   */
  public function addPageElement() {
    if (!$this->page->request->isPost()) {
      header('HTTP/1.1 400 Not a POST request');
      exit;
    }
    $post = $this->page->request->post;

    $page_file_path = SIGNUP_FOLDER."pages/$post->page_id.json";
    if(!is_file($page_file_path)) {
      $this->jsonOutput([
        'error' => "No page with id:$post->page_id",
      ], '404');
      exit;
    } 

    $page_file = file_get_contents($page_file_path);
    $page = json_decode($page_file);
    switch ($post->type) {
      case 'section': // Section
        if(!isset($page->sections)) $page->sections = [];
        $section = [
          'headline' => $post->headline
        ];
        if(isset($page->sections[$post->section_id])) {
          array_splice($page->sections, $post->section_id, 0, [$section]);  
          break;
        }
        $page->sections[$post->section_id] = $section;
        break;
      case 'paragraph':   // Paragraph
      case 'text_input':  // Text Input
      case 'text_area':   // Text Area
      case 'checkbox':    // Checkbox
      case 'date':        // Date
      case 'telephone':   // Telephone
      case 'email':       // Email
      case 'radio':       // Radio
      case 'list':        // List
        // Check section exists
        if(!isset($page->sections[$post->section_id])){
          $this->jsonOutput([
            'error' => "No section with id:$post->section_id on page:$post->page_id",
          ], '400');
          exit;
        }
        $section = $page->sections[$post->section_id];
        // Create item array if not already there
        if(!isset($section->items)){
          $section->items = [];
        }
        // Set item values
        $item = [
          'type' => $post->type,
          'text' => $post->text,
        ];
        if(isset($post->infosys_id)){
          $item['infosys_id'] = $post->infosys_id;
        }
        if($post->type == 'radio' && isset($post->options)) {
          $item['options'] = $post->options;
        }
        // Insert item into section
        if(isset($section->items[$post->item_id])) {
          array_splice($section->items, $post->item_id, 0, [$item]);  
        } else {
          $section->items[$post->item_id] = $item;
        }
        $page->sections[$post->section_id] = $section;
        break;

      case 'option': // Radio/Select Option
        // Check section exists
        if(!isset($page->sections[$post->section_id])){
          $this->jsonOutput([
            'error' => "No section with id:$post->section_id on page:$post->page_id",
          ], '400');
          exit;
        }
        // Check Item exists
        if(!isset($page->sections[$post->section_id]->items[$post->item_id])) {
          $this->jsonOutput([
            'error' => "No item with id:$post->item_id in section:$post->section_id on page:$post->page_id",
          ], '400');
          exit;
        }
        $item = $page->sections[$post->section_id]->items[$post->item_id];
        // Check item is a type that has options
        if ($item->type != 'radio' && $item->type != 'select') {
          $this->jsonOutput([
            'error' => "Item of type:$item->type does not have options",
          ], '400');
          exit;
        }
        // Create option array if not already there
        if(!isset($item->options)){
          $item->options = [];
        }
        // Set option values
        $option = [
          'value' => $post->value,
          'text' => $post->text,
        ];
        // Insert option into item
        if(isset($item->options[$post->option_id])) {
          array_splice($item->options, $post->option_id, 0, [$option]);  
        } else {
          $item->options[$post->option_id] = $option;
        }
        $page->sections[$post->section_id]->items[$post->item_id] = $item;
        break;

      default:
        $this->jsonOutput([
          'error' => "Unknown element type: $post->type",
        ], '404');
        exit;
    }
    file_put_contents($page_file_path, json_encode($page, JSON_PRETTY_PRINT));
    
    $this->jsonOutput([
      'success' => true,
      'text' => $post->text,
    ]);
  }

  /**
   * Edit text value
   */
  public function editText() {
    if (!$this->page->request->isPost()) {
      header('HTTP/1.1 400 Not a POST request');
      exit;
    }
    $post = $this->page->request->post;

    $page_file_path = SIGNUP_FOLDER."pages/$post->page_id.json";
    if(!is_file($page_file_path)) {
      $this->jsonOutput([
        'error' => 'No page with that id',
      ], '404');
      exit;
    } 

    $page_file = file_get_contents($page_file_path);
    $page = json_decode($page_file);

    // Check IDs - Section
    if (in_array($post->type, ['infosys_id','item','setting','headline', 'option', 'value', 'module_id'])){
      if(!isset($post->section_id)) {
        $this->jsonOutput([
          'error' => 'No Section ID',
        ], '400');
        exit;
      }
      if(!isset($page->sections[$post->section_id])) {
        $this->jsonOutput([
          'error' => "Page:$post->page_id Section:$post->section_id doesn't exist",
        ], '400');
        exit;
      }
    }

    // Check IDs - Item
    if (in_array($post->type, ['infosys_id', 'item', 'setting', 'option', 'value'])){
      if(!isset($post->item_id)) {
        $this->jsonOutput([
          'error' => 'No Item ID',
        ], '400');
        exit;
      }
      if(!isset($page->sections[$post->section_id]->items[$post->item_id])) {
        $this->jsonOutput([
          'error' => "Page:$post->page_id Section:$post->section_id Item:$post->item_id doesn't exist",
        ], '400');
        exit;
      }
    }

    // Check IDs - Option
    if (in_array($post->type, ['option', 'value'])){
      if(!isset($post->option_id)) {
        $this->jsonOutput([
          'error' => 'No Option ID',
        ], '400');
        exit;
      }
      if(!isset($page->sections[$post->section_id]->items[$post->item_id]->options[$post->option_id])) {
        $this->jsonOutput([
          'error' => "Page:$post->page_id Section:$post->section_id Item:$post->item_id Option:$post->option_id doesn't exist",
        ], '400');
        exit;
      }
    }

    // Check Language
    if (in_array($post->type, ['item', 'headline', 'title', 'option', 'slug'])){
      if(!isset($post->lang)) {
        $this->jsonOutput([
          'error' => 'No Language(lang) Value',
        ], '400');
        exit;
      }
      if(!in_array($post->lang, ['en', 'da'])) {
        $this->jsonOutput([
          'error' => "Unknown Langugae:$post->lang",
        ], '400');
        exit;
      }
    }

    $text = trim($post->text);
    switch ($post->type) {
      case 'slug':
        $page->slug->{$post->lang} = $text;
        break;
      case 'title':
        $page->title->{$post->lang} = $text;
        break;
      case 'headline':
        $page->sections[$post->section_id]->headline->{$post->lang} = $text;
        break;
      case 'item':
        $page->sections[$post->section_id]->items[$post->item_id]->text->{$post->lang} = $text;
        break;
      case 'setting':
        if ($text == 'false') {
          unset($page->sections[$post->section_id]->items[$post->item_id]->{$post->setting});  
        } else {
          $page->sections[$post->section_id]->items[$post->item_id]->{$post->setting} = $text;
        }
        break;
      case 'infosys_id':
        $page->sections[$post->section_id]->items[$post->item_id]->infosys_id = $text;
        break;
      case 'option':
        $page->sections[$post->section_id]->items[$post->item_id]->options[$post->option_id]->text->{$post->lang} = $text;
        break;
      case 'value':
        $page->sections[$post->section_id]->items[$post->item_id]->options[$post->option_id]->value = $text;
        break;
      case 'module_id':
        $page->sections[$post->section_id]->module = $text;
        break;
        
      default:
        $this->jsonOutput([
          'error' => "Unknown element type: $post->type",
        ], '400');
    }
    $result = file_put_contents($page_file_path, json_encode($page, JSON_PRETTY_PRINT));

    if ($result === false) {
      $this->jsonOutput([
        'status' => 'error',
        'message' => "could not write to file $post->page_id.json",
      ], 500);
    }
    
    $this->jsonOutput([
      'success' => true,
      'text' => $text,
    ]);
  }

  public function editConfig() {
    if (!$this->page->request->isPost()) {
      header('HTTP/1.1 400 Not a POST request');
      exit;
    }
    $post = $this->page->request->post;

    $module = $this->vars['module'];
    $config_file_path = SIGNUP_FOLDER."config/$module.json";
    if(!is_file($config_file_path)) {
      $this->jsonOutput([
        'status' => 'error',
        'message' => "No module named $module",
      ], '404');
    } 

    $config_file = file_get_contents($config_file_path);
    $config = json_decode($config_file);

    $keys = explode('::', $post->id);
    if (!$this->edit_nested($keys, $post->text, $config)) {
      $this->jsonOutput([
        'status' => 'error',
        'message' => "No setting with ID:$post->id",
      ], '400');
    }

    $result = file_put_contents($config_file_path, json_encode($config, JSON_PRETTY_PRINT));

    if ($result === false) {
      $this->jsonOutput([
        'status' => 'error',
        'message' => "could not write to file $module.json",
      ], 500);
    }

    $this->jsonOutput([
      'status' => 'success',
      'text' => $post->text,
    ]);
  }

  private function edit_nested($keys, $value, &$object) {
    $key = array_shift($keys);
    if (!isset($object->$key)) return false;

    if(count($keys) == 0) {
      $object->$key = $value;
      return true;
    }

    return $this->edit_nested($keys, $value, $object->$key);
  }
}