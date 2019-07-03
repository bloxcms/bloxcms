<?php

class Text
{
    /**
     * @todo Remake this method via Text::balanceTags()
     * @todo Move $length to $options['length']
     *
     * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
     *
     * @todo Bugs:
     *    1.
     *    $aa = '<b>012345</b>6789ab';
     *    $aa = Text::truncate($aa, 10, ['ellipsis'=>'...']);
     *        Выдает неправильно: <b>012345<...</b>       то есть ellipsis в том числе
     *    Но при 'split-word'=>true
     *        Выдает правильно: <b>012345</b>6789...      ellipsis плюсуется
     *        
     *    2.
     *    BUG: У этого текста после обработки Text::truncate(..., 266) будет отсутствовать последний div! 
     *    Было:
     *    <div>
     *    <div><span style="font-size:14px;">Компания &laquo;ИНТЕРСКОЛ&raquo; провела глубокую техническую модернизацию двух популярных моделей угловых шлифовальных машин УШМ-115/900 и УШМ-125/900. Их ресурс увеличился на 50%, а мощность возросла на 100 Вт, позволяя более эффективно работать с твердыми материалами.</span></div>
     *    </div>
     *    Стало:
     *    <div>
     *    <div><span style="font-size:14px;">Компания &laquo;ИНТЕРСКОЛ&raquo; провела глубокую техническую модернизацию двух популярных моделей угловых шлифовальных машин УШМ-115/900 и УШМ-125/900. Их ресурс увеличился на 50%, а мощность возросла на 100 Вт, позволяя более эффективно работать с твердыми...</span></div>
     *    Существует какая-то критическая длина она может быть связана с длиной ellipsis. 
     *    Убрал учет длины ellipsis - не помогло.
     */
    
	public static function truncate($text, $length=100, $options=[]) 
    {
        if ($options)
            Arr::formatOptions($options);
        
        # Defaults
        $options += ['ellipsis'=>'...', 'split-word'=>false, 'plain'=>false]; 
           
        # If there are tags
        if (!$options['plain'] && preg_match('~<.+?>~u', $text)){
            $options['correct-closing-tags'] = true;
            #$options['ellipsis'] = "\xe2\x80\xa6";// && Configure::read('App.encoding') === 'UTF-8') 
        }
        
		if ($options['correct-closing-tags']) 
        {
			if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length)
				return $text;
            $totalLength = '';
			$openTags = [];
			$truncate = '';

			preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $taggs, PREG_SET_ORDER);
			foreach ($taggs as $tagg) {
				if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tagg[2])) {
					if (preg_match('/<[\w]+[^>]*>/s', $tagg[0])) {
						array_unshift($openTags, $tagg[2]);
					} elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tagg[0], $closeTag)) {
						$pos = array_search($closeTag[1], $openTags);
						if ($pos !== false)
							array_splice($openTags, $pos, 1);}
				}
				$truncate .= $tagg[1];
				$contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tagg[3]));
				if ($contentLength + $totalLength > $length) {
					$left = $length - $totalLength;
					$entitiesLength = 0;
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tagg[3], $entities, PREG_OFFSET_CAPTURE)) {
						foreach ($entities[0] as $entity) {
							if ($entity[1] + 1 - $entitiesLength <= $left) {
								$left--;
								$entitiesLength += mb_strlen($entity[0]);
							} else {
								break;
							}
						}
					}

					$truncate .= mb_substr($tagg[3], 0, $left + $entitiesLength);
					break;
				} else {
					$truncate .= $tagg[3];
					$totalLength += $contentLength;
				}
				if ($totalLength >= $length) {
					break;
				}
			}
		} else {
			if (mb_strlen($text) <= $length) {
				return $text;
			}
			##$truncate = mb_substr($text, 0, $length - mb_strlen($options['ellipsis']));
            $truncate = mb_substr($text, 0, $length);
		}
		if (!$options['split-word']) {
			$spacepos = mb_strrpos($truncate, ' ');
			if ($options['correct-closing-tags']) {
				$truncateCheck = mb_substr($truncate, 0, $spacepos);
				$lastOpenTag = mb_strrpos($truncateCheck, '<');
				$lastCloseTag = mb_strrpos($truncateCheck, '>');
				if ($lastOpenTag > $lastCloseTag) {
					preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
					$lastTag = array_pop($lastTagMatches[0]);
					$spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
				}
				$bits = mb_substr($truncate, $spacepos);
				preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
				if ($droppedTags) {
					if ($openTags) {
						foreach ($droppedTags as $closingTag) {
							if (!in_array($closingTag[1], $openTags)) {
								array_unshift($openTags, $closingTag[1]);
							}
						}
					} else {
						foreach ($droppedTags as $closingTag) {
							$openTags[] = $closingTag[1];
						}
					}
				}
			}
			$truncate = mb_substr($truncate, 0, $spacepos);
            # If truncate still empty, then we don't need to count ellipsis in the cut.
            if (mb_strlen($truncate) === 0) {
                $truncate = mb_substr($text, 0, $length);
            }
		}
		$truncate .= $options['ellipsis'];

		if ($options['correct-closing-tags'])
			foreach ($openTags as $tagg)
				$truncate .= '</' . $tagg . '>';

		return $truncate;
	}



    /**
     * @todo Remake $options format by Arr::formatOptions($options)
     *
     * 
     */
    public static function stripTags($text, $options=[])
    {
        if (!$text || !is_string($text))
            return $text;

        if ($options)
            Arr::formatOptions($options);
        # Defaults
        $options += ['strip-quotes'=>false, 'nl2br'=>false, 'tab2nbsp'=>false, 'exceptions'=>''];
        if ($options['exceptions']) {
            if (is_array($options['exceptions'])) {
                foreach ($options['exceptions'] as $ex)
                    $exceptionsList .= '|'.$ex;
                $exceptionsList = substr($exceptionsList, 1);
            } else
                $exceptionsList = $options['exceptions'];
            if ($exceptionsList)
                $exceptionsPattern = '(?!/?('.$exceptionsList.')\b)';# \b - a word boundary
        }
        
        //$text = self::removeElements($text, 'script|style')
        
        # remove html tags except some 
        $pattern = '~<'.$exceptionsPattern.'[^>]*>~ui';
        $text = preg_replace($pattern, ' ', $text); # strip_tags() removes "<br>", but not replaces it with space!
        # Replace all mnemonics, including quotes, by symbols.
        # ENT_QUOTES	Will convert both double and single quotes.
        # $text = html_entity_decode($text, ENT_QUOTES, 'utf-8'); 
        $text = str_replace('&nbsp;', ' ', $text);   #  replace no-break spaces
        $text = str_replace("\xc2\xa0",' ', $text);   #  replace no-break spaces as UTF-8
            
        # New lines to breaks
        $r = ($options['nl2br']) ? '<br />' : '';
        if ($options['nl2br']) {
            $text = str_replace(
                ["\r\n", "\r", "\n"], # Do not remove "\r\n" otherwise double breakes appear!   # $text = nl2br($text); this function does NOT replace newlines! 
                $r, 
                $text
            );
        }
        #
        $r = ($options['tab2nbsp']) ? '&nbsp;&nbsp;&nbsp;&nbsp;' : ' ';
        if ($options['tab2nbsp']) {
            $text = str_replace(
                "\t", 
                $r, 
                $text
            );  
        }
        ##Reserved
        ##$text = preg_replace('~\s{2,}~', ' ', $text); # remove multiple spaces. It removes new lines too! (nl2br)
        ##$text = trim($text);

        if ($options['strip-quotes'])
            $text = str_replace(['"',"'"], '', $text);

        return $text;
    }
    
    
    /**
     * Remove all attributes
     * @param string $text 
     * @return string 
     *
     * @todo Remove only style:  preg_replace('~(<[^>]+) style=".*?"~i', '$1', $text);
     * @todo Text::removeFormat()
     */
    public static function removeAttributes($text)
    {
        return preg_replace("~<([a-z][a-z0-9]*)[^>]*?(\/?)>~i",'<$1$2>', $text);
    }
    

    /**
     * Remove elements with specified tags
     * @param string $text 
     * @param mixed $tags 'script|style' or [script','style']
     * @return string 
     */
    public static function removeElements($text, $tags)
    {   
        if ($tags) {
            if (is_array($tags)) {
                foreach ($tags as $t)
                    $tagsList .= '|'.$t;
                $tagsList = substr($tagsList, 1);
            } else
                $tagsList = $tags;
        }
                
        if ($tagsList)
            return preg_replace('~<('.$tagsList.')[^>]*?>.*?</\\1>~si', '', $text);
        else
            return $text;
    }
    
    
    /**
     * Made on the basis of the Wordpress wp-includes/formatting.php > force_balance_tags()
     * Balances tags of string using a modified stack.
     *
     * @since 2.0.4
     *
     * @author Leonard Lin <leonard@acm.org>
     * @license GPL
     * @copyright November 4, 2001
     * @version 1.1
     * @todo Make better - change loop condition to $text in 1.2
     * @internal Modified by Scott Reilly (coffee2code) 02 Aug 2004
     *		1.1  Fixed handling of append/stack pop order of end text
     *			 Added Cleaning Hooks
     *		1.0  First Version
     *
     * @param string $text Text to be balanced.
     * @return string Balanced text.
     */
    public static function balanceTags($text)
    {
    	$tagstack = array();
    	$stacksize = 0;
    	$tagqueue = '';
    	$newtext = '';
    	// Known single-entity/self-closing tags
    	$single_tags = array( 'area', 'base', 'basefont', 'br', 'col', 'command', 'embed', 'frame', 'hr', 'img', 'input', 'isindex', 'link', 'meta', 'param', 'source' );
    	// Tags that can be immediately nested within themselves
    	$nestable_tags = array( 'blockquote', 'div', 'object', 'q', 'span' );

    	// WP bug fix for comments - in case you REALLY meant to type '< !--'
    	$text = str_replace('< !--', '<    !--', $text);
    	// WP bug fix for LOVE <3 (and other situations with '<' before a number)
    	$text = preg_replace('#<([0-9]{1})#', '&lt;$1', $text);

    	while ( preg_match("/<(\/?[\w:]*)\s*([^>]*)>/", $text, $regex) ) {
    		$newtext .= $tagqueue;

    		$i = strpos($text, $regex[0]);
    		$l = strlen($regex[0]);

    		// clear the shifter
    		$tagqueue = '';
    		// Pop or Push
    		if ( isset($regex[1][0]) && '/' == $regex[1][0] ) { // End Tag
    			$tag = strtolower(substr($regex[1],1));
    			// if too many closing tags
    			if ( $stacksize <= 0 ) {
    				$tag = '';
    				// or close to be safe $tag = '/' . $tag;
    			}
    			// if stacktop value = tag close value then pop
    			elseif ( $tagstack[$stacksize - 1] == $tag ) { // found closing tag
    				$tag = '</' . $tag . '>'; // Close Tag
    				// Pop
    				array_pop( $tagstack );
    				$stacksize--;
    			} else { // closing tag not at top, search for it
    				for ( $j = $stacksize-1; $j >= 0; $j-- ) {
    					if ( $tagstack[$j] == $tag ) {
    					// add tag to tagqueue
    						for ( $k = $stacksize-1; $k >= $j; $k--) {
    							$tagqueue .= '</' . array_pop( $tagstack ) . '>';
    							$stacksize--;
    						}
    						break;
    					}
    				}
    				$tag = '';
    			}
    		} else { // Begin Tag
    			$tag = strtolower($regex[1]);

    			// Tag Cleaning

    			// If it's an empty tag "< >", do nothing
    			if ( '' == $tag ) {
    				// do nothing
    			}
    			// ElseIf it presents itself as a self-closing tag...
    			elseif ( substr( $regex[2], -1 ) == '/' ) {
    				// ...but it isn't a known single-entity self-closing tag, then don't let it be treated as such and
    				// immediately close it with a closing tag (the tag will encapsulate no text as a result)
    				if ( ! in_array( $tag, $single_tags ) )
    					$regex[2] = trim( substr( $regex[2], 0, -1 ) ) . "></$tag";
    			}
    			// ElseIf it's a known single-entity tag but it doesn't close itself, do so
    			elseif ( in_array($tag, $single_tags) ) {
    				$regex[2] .= '/';
    			}
    			// Else it's not a single-entity tag
    			else {
    				// If the top of the stack is the same as the tag we want to push, close previous tag
    				if ( $stacksize > 0 && !in_array($tag, $nestable_tags) && $tagstack[$stacksize - 1] == $tag ) {
    					$tagqueue = '</' . array_pop( $tagstack ) . '>';
    					$stacksize--;
    				}
    				$stacksize = array_push( $tagstack, $tag );
    			}

    			// Attributes
    			$attributes = $regex[2];
    			if ( ! empty( $attributes ) && $attributes[0] != '>' )
    				$attributes = ' ' . $attributes;

    			$tag = '<' . $tag . $attributes . '>';
    			//If already queuing a close tag, then put this tag on, too
    			if ( !empty($tagqueue) ) {
    				$tagqueue .= $tag;
    				$tag = '';
    			}
    		}
    		$newtext .= substr($text, 0, $i) . $tag;
    		$text = substr($text, $i + $l);
    	}

    	// Clear Tag Queue
    	$newtext .= $tagqueue;

    	// Add Remaining text
    	$newtext .= $text;

    	// Empty Stack
    	while( $x = array_pop($tagstack) )
    		$newtext .= '</' . $x . '>'; // Add remaining tags to close

    	// WP fix for the bug with HTML comments
    	$newtext = str_replace("< !--","<!--",$newtext);
    	$newtext = str_replace("<    !--","< !--",$newtext);

    	return $newtext;
    }
    
}