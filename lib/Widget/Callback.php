<?php
/**
 * Widget Framework
 *
 * @copyright   Twin Huang
 * @license     http://www.opensource.org/licenses/apache2.0.php Apache License
 */

namespace Widget;

use \Closure;
use \SimpleXMLElement;

/**
 * The widget for WeChat(Weixin) callback message
 *
 * @author      Twin Huang <twinh@yahoo.cn>
 * @link        http://mp.weixin.qq.com/wiki/index.php?title=%E6%B6%88%E6%81%AF%E6%8E%A5%E5%8F%A3%E6%8C%87%E5%8D%97
 * @method      \Widget\Response response(string $content, int $status = 200) Send headers and output content
 * @method      string query(string $name) Returns the URL query parameter value
 */
class Callback extends AbstractWidget
{
    /**
     * The callback token to generate signature
     * 
     * @var string
     */
    protected $token = 'widget';
     
    /**
     * The HTTP raw post data, equals to $GLOBALS['HTTP_RAW_POST_DATA'] on default
     * 
     * @var string
     */
    protected $postData;
    
    /**
     * The rules to generate response message
     * 
     * @var array
     */
    protected $rules = array(
        'text'      => array(),
        'event'     => array(),
        'image'     => null,
        'location'  => null,
        'voice'     => null,
        'video'     => null,
        'link'      => null
    );
    
    /**
     * Whether return the mark message or not
     * 
     * @var bool
     */
    protected $mark = false;
    
    /**
     * @var string
     * @todo better name
     */
    protected $fallback;
    
    /**
     * Are there any callbacks handled the message ?
     * 
     * @var bool
     */
    protected $handled = false;
    
    protected $toUserName;
    
    protected $fromUserName;
    
    protected $createTime;
    
    protected $msgId;
    
    protected $msgType;
    
    protected $content;
    
    protected $picUrl;
    
    protected $locationX;
    
    protected $locationY;
    
    protected $scale;
    
    protected $label;
    
    protected $mediaId;
    
    protected $format;
   
    protected $event;

    protected $eventKey;
    
    protected $thumbMediaId;
    
    protected $title;
    
    protected $description;
    
    protected $url;
    
    /**
     * Constructor
     * 
     * @param array $options
     * @global string $GLOBALS['HTTP_RAW_POST_DATA'] 
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        
        if (is_null($this->postData) && isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            $this->postData = $GLOBALS['HTTP_RAW_POST_DATA'];
        }
    }

    /**
     * Parse the user input message and response matched rule message
     * 
     * @return \Widget\Callback
     */
    public function __invoke()
    {
        // Check if it's requested from the WeChat server
        if ($this->checkSignature()) {
            if ($echostr = $this->query('echostr')) {
                // Response echostr for fist time authentication
                $this->response(htmlspecialchars($echostr, \ENT_QUOTES, 'UTF-8'));
                return $this;
            }
        } else {
            $this->response('Forbidden', 403);
            return $this;
        }

        // Parse user input data
        $this->parsePostData();
        
        switch ($this->msgType) {
            case 'text' :
                $this->handleText();
                break;
                
            case  'event':
                $eventRule = $this->rules['event'];
                if (isset($eventRule[$this->event])
                    && isset($eventRule[$this->event][$this->eventKey])) {
                    $this->handle($eventRule[$this->event][$this->eventKey]);
                }
                break;
                
            case 'location':
            case 'image':
            case 'voice':
            case 'video':
            case 'link':
                if (isset($this->rules[$this->msgType])) {
                    $this->handle($this->rules[$this->msgType]);
                }
                break;
        }
        
        if (!$this->handled && $this->fallback) {
            $this->handle($this->fallback);
        }
        
        return $this;
    }
    
    /**
     * Attach a callback which triggered when user subscribed you
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function subscribe(Closure $fn)
    {
        return $this->addEventRule('subscribe', null, $fn);
    }
    
    /**
     * Attach a callback which triggered when user unsubscribed you
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function unsubscribe(Closure $fn)
    {
        return $this->addEventRule('unsubscribe', null, $fn);
    }

    /**
     * Attach a callback which triggered when user click the custom menu
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function click($key, Closure $fn)
    {
        return $this->addEventRule('click', $key, $fn);
    }
    
    /**
     * Attach a callback which triggered when user input equals to the keyword
     * 
     * @param string $keyword The keyword to compare with user input
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function is($keyword, Closure $fn)
    {
        return $this->addRule('is', $keyword, $fn);
    }
    
    /**
     * Attach a callback with a keyword, which triggered when user input contains the keyword
     * 
     * @param string $keyword The keyword to search in user input
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function has($keyword, Closure $fn)
    {
        return $this->addRule('has', $keyword, $fn);
    }
    
    /**
     * Attach a callback with a regex pattern which triggered when user input match the pattern
     * 
     * @param string $pattern The pattern to match
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function match($pattern, Closure $fn)
    {
        return $this->addRule('match', $pattern, $fn);
    }
    
    /**
     * Attach a callback to handle image message  
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function receiveImage(Closure $fn)
    {
        $this->rules['image'] = $fn;
        return $this;
    }
    
    /**
     * Attach a callback to handle location message
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function receiveLocation(Closure $fn)
    {
        $this->rules['location'] = $fn;
        return $this;
    }
    
    /**
     * Attach a callback to handle voice message
     * 
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    public function receiveVoice(Closure $fn)
    {
        $this->rules['voice'] = $fn;
        return $this;
    }
    
    /**
     * Attach a callback to handle video message
     * 
     * @param Closure $fn
     * @return \Widget\Callback
     */
    public function receiveVideo(Closure $fn)
    {
        $this->rules['video'] = $fn;
        return $this;
    }
    
    /**
     * Attach a callback to handle link message
     * 
     * @param Closure $fn
     * @return \Widget\Callback
     */
    public function receiveLink(Closure $fn)
    {
        $this->rules['link'] = $fn;
        return $this;
    }
    
    /**
     *  Attach a callback which triggered when none of the rule handled the input
     * 
     * @param \Closure $fn
     * @return boolean
     */
    public function fallback(Closure $fn)
    {
        $this->fallback = $fn;
        return $this;
    }
    
    /**
     * Generate text message for response
     * 
     * @param string $content
     * @param bool $mark Whenter mark the message or not
     * @return \SimpleXMLElement
     */
    public function sendText($content, $mark = null)
    {
        $xml = $this->createXml();

        $this->addCDataChild($xml, 'Content', $content);
        
        return $this->send('text', $xml, $mark);
    }
    
    /**
     * Generate music message for response
     * 
     * @param string $title The title of music
     * @param string $description The description display blow the title
     * @param string $url The music URL for player
     * @param string $hqUrl The HQ music URL for player when user in WIFI
     * @param string $mark Whenter mark the message or not
     * @return \SimpleXMLElement
     */
    public function sendMusic($title, $description, $url, $hqUrl = null, $mark = null)
    {
        $xml    = $this->createXml();
        $music  = $xml->addChild('Music');
        
        $this
            ->addCDataChild($music, 'Title', $title)
            ->addCDataChild($music, 'Description', $description)
            ->addCDataChild($music, 'MusicUrl', $url)
            ->addCDataChild($music, 'HQMusicUrl', $hqUrl);

        return $this->send('music', $xml, $mark);    
    }
    
    /**
     * Generate article message for response
     * 
     * @param array $articles The article array
     * @param bool $mark Whenter mark the message or not
     * @return \SimpleXMLElement 
     */
    public function sendArticle(array $articles, $mark = null)
    {
        $xml = $this->createXml();

        // Convert single article array
        if (!is_int(key($articles))) {
            $articles = array($articles);
        }
        
        $xml->addChild('ArticleCount', count($articles));
        
        $items = $xml->addChild('Articles');
        foreach ($articles as $article) {
            $article += array(
                'title' => null,
                'description' => null,
                'picUrl' => null,
                'url' => null
            );
            $item = $items->addChild('item');
            $this
                ->addCDataChild($item, 'Title', $article['title'])
                ->addCDataChild($item, 'Description', $article['description'])
                ->addCDataChild($item, 'PicUrl', $article['picUrl'])
                ->addCDataChild($item, 'Url', $article['url']);
        }

        return $this->send('news', $xml, $mark);
    }
    
    /**
     * Whether return the mark message or not
     * 
     * @param bool $bool
     * @return \Widget\Callback
     */
    public function setMark($bool)
    {
        $this->mark = (bool)$bool;
        return $this;
    }
    
    /**
     * Returns your user id
     * 
     * @return string
     */
    public function getToUserName()
    {
        return $this->toUserName;
    }
    
    /**
     * Reurns a user(OpenID) who sent message to you
     * 
     * @return string
     */
    public function getFromUserName()
    {
        return $this->fromUserName;
    }

    /**
     * Returns the timestamp when message created
     * 
     * @var string
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }
    
    /**
     * Returns the user input string, available when the message type is text
     * 
     * @var string
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Returns the message id
     * 
     * @return string
     */
    public function getMsgId()
    {
        return $this->msgId;
    }
    
    /**
     * Returns the message type
     * 
     * Currently could be text, image, location, link, event, voice, video
     * 
     * @return string
     */
    public function getMsgType()
    {
        return $this->msgType;
    }
    
    /**
     * Returns the picture URL, available when the message type is image
     * 
     * @var string
     */
    public function getPicUrl()
    {
        return $this->picUrl;
    }
    
    /**
     * Returns the latitude of location, available when the message type is location
     * 
     * @var string
     */
    public function getLocationX()
    {
        return $this->locationX;
    }
    
    /**
     * Returns the longitude of location, available when the message type is location
     * 
     * @var string
     */
    public function getLocationY()
    {
        return $this->locationY;
    }
    
    /**
     * Returns the detail address of location, available when the message type is location
     * 
     * @var string 
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Returns the scale of map, available when the message type is location
     * 
     * @var string
     */
    public function getScale()
    {
        return $this->scale;
    }
    
    /**
     * Returns the media id, available when the message type is voice or video
     * 
     * @var string
     */
    public function getMediaId()
    {
        return $this->mediaId;
    }
    
    /**
     * Returns the media format, available when the message type is voice
     * 
     * @var string
     */
    public function getFormat()
    {
        return $this->format;
    }
    
    /**
     * Returns the type of event, could be subscribe, unsubscribe or CLICK, available when the message type is event
     * 
     * @var string
     */
    public function getEvent()
    {
        return $this->event;
    }
    
    /**
     * Returns the key value of custom menu, available when the message type is event
     * 
     * @var string
     */
    public function getEventKey()
    {
        return $this->eventKey;
    }
    
    /**
     * Returns the thumbnail id of video, available when the message type is video
     * 
     * @var string 
     */
    public function getThumbMediaId()
    {
        return $this->thumbMediaId;
    }
    
    /**
     * Returns the title of URL, available when the message type is link
     * 
     * @var string
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * Returns the description of URL, available when the message type is link
     * 
     * @var string
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Returns the URL link, available when the message type is link
     * 
     * @var string
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    protected function handle($fn)
    {
        $this->handled = true;
        
        $content = $fn($this, $this->widget);
        if (!$content instanceof SimpleXMLElement) {
            $content = $this->sendText($content);
        }

        $this->response($content->asXML());
    }
        
    /**
     * Generate message for response
     * 
     * @param string $type The type of message
     * @param \SimpleXMLElement $body The body of message
     * @param bool $mark $mark Whenter mark the message or not
     * @return \SimpleXMLElement
     */
    protected function send($type, SimpleXMLElement $xml, $mark = null)
    {
        $this
            ->addCDataChild($xml, 'ToUserName', $this->fromUserName)
            ->addCDataChild($xml, 'FromUserName', $this->toUserName)
            ->addCDataChild($xml, 'MsgType', $type);
        
        $xml->addChild('CreateTime', time());
        $xml->addChild('FuncFlag', (int)(is_bool($mark) ? $mark : $this->mark));
        
        return $xml;
    }

    /**
     * Create a root xml object 
     * 
     * @return \SimpleXMLElement
     */
    protected function createXml()
    {
        $xml = new SimpleXMLElement('<xml/>');
        
        return $xml;
    }
    
    /**
     * Adds a cdata section to specified xml object
     * 
     * @param \SimpleXMLElement $xml
     * @param string $name
     * @param string $value
     * @return \Widget\Callback
     */
    protected function addCDataChild(SimpleXMLElement $xml, $name, $value)
    {
        $child = $xml->addChild($name);
        $node = dom_import_simplexml($child); 
        $node->appendChild($node->ownerDocument->createCDATASection($value));
        return $this;
    }
    
    /**
     * Adds rule to handle user input
     * 
     * @param string $type
     * @param string $keyword
     * @param \Closure $fn
     * @return \Widget\Callback
     */
    protected function addRule($type, $keyword, Closure $fn)
    {
        $this->rules['text'][] = array(
            'type' => $type,
            'keyword' => $keyword,
            'fn' => $fn
        );
        return $this;
    }
    
    protected function addEventRule($name, $key, Closure $fn)
    {
        $this->rules['event'][$name][$key] = $fn;
        return $this;
    }
    
    /**
     * Parse post data to recive user OpenID and input content and more
     * 
     * @todo detect invald input
     */
    protected function parsePostData()
    {
        $defaults = array('FromUserName', 'ToUserName', 'MsgId', 'CreateTime');
        $fields = array(
            'text'      => array('Content'),
            'image'     => array('PicUrl'),
            'location'  => array('Location_X', 'Location_Y', 'Scale', 'Label'),
            'voice'     => array('MediaId', 'Format'),
            'event'     => array('Event', 'EventKey'),
            'video'     => array('MediaId', 'ThumbMediaId'),
            'link'      => array('Title', 'Description', 'Url')
        );
        
        if ($this->postData) {
            $postObj        = simplexml_load_string($this->postData, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->msgType  = isset($postObj->MsgType) ? (string)$postObj->MsgType : null;
            if (isset($fields[$this->msgType])) {
                foreach (array_merge($defaults,$fields[$this->msgType]) as $field) {
                    if (isset($postObj->$field)) {
                        $name = lcfirst(strtr($field, array('_' => '')));
                        $this->$name = (string)$postObj->$field;
                    }
                }
            }         
        }
    }
    
    /**
     * Check if the signature is valid
     * 
     * @return bool
     */
    protected function checkSignature()
    {
        $tmpArr = array(
            $this->token, 
            $this->query('timestamp'),
            $this->query('nonce')
        );
        sort($tmpArr);
        $tmpStr = sha1(implode($tmpArr));
        return $tmpStr === $this->query('signature');
    }
    
    protected function handleText()
    {
        foreach ($this->rules['text'] as $rule) {
            $matched = false;
            switch ($rule['type']) {
                case 'is':
                    $matched = $this->content == $rule['keyword'];
                    break;
                    
                case 'has' :
                    $matched = false !== strpos($this->content, $rule['keyword']);
                    break;
                
                case 'match':
                    $matched = preg_match($rule['keyword'], $this->content);
                    break;
            }
            if ($matched) {
                return $this->handle($rule['fn']);
            }
        }
    }
}
