<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\Maps\PersistanceMap;
use Cobalt\SchemaPrototypes\Compound\UserIdResult;
use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Cobalt\SchemaPrototypes\Traits\Prototype;
use DOMDocument;
use Drivers\FileSystem;
use Exception;
use ParsedownExtra;
use Validation\Exceptions\ValidationIssue;

/** @package Cobalt\SchemaPrototypes\Basic */
class BlockResult extends SchemaResult {
    use Fieldable;
    protected $type = "block-editor";
    protected bool $asHTML = true;

    #[Prototype]
    protected function field(string $class = "", array $misc = [], string $tag = "block-editor"):string {
        if($this->getDirective("private")) return "";
        if($this->getDirective("immutable")) $misc['readonly'] = 'readonly';
        [$misc, $attrs] = $this->defaultFieldData($misc);
        $html = "<$tag class=\"$class\" $attrs>";
        $html .= "<script type=\"application/json\">".json_encode($this->getRaw())."</script>";
        $html .= "</$tag>";
        return $html;
    }

    #[Prototype]
    protected function firstParagraph():string {
        // Loop through our block content until we find the first instance of a paragraph
        foreach($this->value->blocks as $val => $details) {
            if($details->type === "paragraph") return $details->data->text;
        }
        // If there's no paragraph data, just return an empty string
        return "";
    }

    #[Prototype]
    protected function tableOfContents():string {
        $body = $this->getRaw();
        // Since headline levels only go up to six, we'll start at 10
        $header = 10;
        $subheader = 11;
        $headlines = [];
        // Loop through our blocks and build a list of headlines
        // We'll also take this list to determine our baseline header values
        foreach($body->blocks as $block) {
            if($block->type !== "header") continue;
            if($block->data->level < $header) $header = $block->data->level;
            $headlines[] = $block;
        }

        // Now that have a baseline header, we'll set our other header
        $subheader = $header + 1;

        $ol = "<nav><ol class=\"blockeditor--index level--$header\" rel=\"Table of Contents\">";

        // $tagOpen = false;
        $previousHeadlineLevel = $header;

        foreach($headlines as $block) {
            $currentBlockLevel = $block->data->level;
            // If our subheader is 2 and our current block is 3, skip.
            if($currentBlockLevel > $subheader) continue;

            // If our current block is a $header
            if($currentBlockLevel === $header && $currentBlockLevel !== $previousHeadlineLevel) {
                // Check if our tag is 
                $ol .= "</ol>";
                // $tagOpen = false;
            }
            
            if($currentBlockLevel === $subheader && $currentBlockLevel !== $previousHeadlineLevel) {
                // $tagOpen = true;
                $ol .= "<ol class=\"blockeditor--index level--$currentBlockLevel\">";
            }
            $id = $block->data->id ?? $block->id;
            $ol .= "<li><a href=\"#$id\">".$block->data->text."</a></li>";
            $previousHeadlineLevel = $currentBlockLevel;
        }

        return $ol . "</ol></nav>";
    }

    #[Prototype]
    protected function timeToRead($format = "round") {
        // $word_count = (string)$this;
        $fmt = TIME_TO_READ_FORMAT_ROUND;
        if($format !== "round") $fmt = TIME_TO_READ_FORMAT_MINSEC;
        
        return time_to_read((string)$this, $fmt);
    }

    #[Prototype]
    protected function lastModified($format = "Y-m-d") {
        return date($format, $this->value['time'] / 1000);
    }

    function filter($value) {
        $manager = null;
        $orphaned_images = [];
        if($this->__reference instanceof PersistanceMap) $manager = $this->__reference->__get_manager();
        if($manager) {
            $full_document = $manager->findOne(['_id' => $this->__reference->_id]);
            foreach($full_document->{$this->name}->blocks as $blk) {
                if($blk['type'] === 'imagetool') {
                    $orphaned_images[$blk['id']] = $blk;
                }
            }
        }
        foreach($value['blocks'] as $index => $block) {
            switch($block['type']) {
                case "paragraph":
                    $value['blocks'][$index] = $this->filter_paragraph($block);
                    break;
                case "rawtool":
                    $value['blocks'][$index] = $this->filter_htmltool($block);
                    break;
                case "header":
                    $value['blocks'][$index] = $this->filter_header($block);
                    break;
                case "imagetool":
                    $value['blocks'][$index] = $this->filter_imagetool($block, $orphaned_images);
                    break;
                case "simpleimage":
                    $value['blocks'][$index] = $this->filter_simpleimage($block);
                    break;
            }
        }

        if(count($orphaned_images)) header("X-Message: @warning There are ".count($orphaned_images)." orphaned images from this post!");

        return $value;
    }

    private function filter_paragraph(&$block):array {
        return $block;
    }

    private function filter_htmltool(&$block):array {
        if(!$block['data']['html']) return $block;
        $dom = new DOMDocument();
        try {
            $parsed = $dom->loadHTML(mb_convert_encoding($block['data']['html'], 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_NOERROR);
        } catch (Exception $e) {
            throw new ValidationIssue("Raw HTML must evaluate to valid HTML");
        }
        
        if($parsed === false) throw new ValidationIssue("The Raw HTML tool must evaluate");
        // $scripts = $dom->getElementsByTagName("script");
        // /** @var DOMElement */
        // foreach($scripts as $script) {
        //     if($script->textContent) throw new ValidationIssue("script tags may only load scripts, they cannot contain JavaScript!");
        // }
        return $block;
    }

    private function filter_header(&$block):array {
        if(!$block['data']['text']) return $block;
        $block['data']['id'] = url_fragment_sanitize($block['data']['text']);
        return $block;
    }

    private function filter_imagetool(&$block, &$orphaned_images):array {
        if(key_exists($block['id'], $orphaned_images)) unset($orphaned_images[$block['id']]);
        return $block;
    }

    private function filter_simpleimage(&$block): array {
        if(is_data_uri($block['data']['url'])) {
            $tmp_name = "/tmp/".uniqid("", true);
            $result = convert_data_uri_to_file($tmp_name, $block['data']['url']);
            if($result === false) throw new ValidationIssue("Failed to convert a data URI to a file");
            $aesthetic_name = random_string(24).".".get_extension_from_file($tmp_name);
            $fs = new FileSystem();
            $result = $fs->upload([
                'name' => $aesthetic_name,
                'tmp_name' => $tmp_name
            ]);
            $block['data']['url'] = "/res/fs/" . $aesthetic_name;
        }
        return $block;
    }

    function __toString(): string {
        $val = $this->getValue();
        $html = "";
        foreach($val->blocks as $index => $block) {
            switch($block->type) {
                case "paragraph":
                    $html .= $this->__from_paragraph($block);
                    break;
                case "header":
                    $html .= $this->__from_header($block);
                    break;
                case "simpleimage":
                    $html .= $this->__from_simpleimage($block);
                    break;
                case "imagetool":
                    $html .= $this->__from_imagetool($block);
                    break;
                case "linktool":
                    $html .= $this->__from_linktool($block);
                    break;
                case "quote":
                    $html .= $this->__from_quote($block);
                    break;
                case "rawtool":
                    $html .= $this->__from_rawtool($block);
                    break;
                case "nestedlist":
                    $html .= $this->__from_nestedlist($block);
                    break;
                case "table":
                    $html .= $this->__from_table($block);
                    break;
                case "embed":
                    $html .= $this->__from_embed($block);
                    break;
                case "codetool":
                    $html .= $this->__from_codetool($block);
                    break;
                case "blockbutton":
                    $html .= $this->__from_blockbutton($block);
                    break;
                case "reply":
                    $html .= $this->__from_reply($block);
                    break;
                case "like":
                    $html .= $this->__from_like($block);
                    break;
                default:
                    $html .= "<pre>Cannot render type: $block->type</pre>";
                    break;
            }
        }
        return $html;
    }

    private function __from_paragraph($block) {
        $html = "<p class=\"blockeditor--content blockeditor--paragraph\">";
        if(__APP_SETTINGS__['BlockContent_paragraph_external_links_to_blank']) {
            $html .= add_target_blank_to_external_links($block->data->text);
        } else $html .= $block->data->text;
        $html .= "</p>";
        return $html;
    }

    private function __from_header($block) {
        $tag = "h". $block->data->level;
        $id = $block->data->id ?? $block->id;
        return "<$tag id=\"$id\" class=\"blockeditor--content blockeditor--header\">" . $block->data->text . "</$tag>";
    }

    private function __from_simpleimage($block) {
        $classes = "blockeditor--content blockeditor--image blockeditor--simpleimage";
        if($block->data->withBorder) $classes .= " blockeditor--image-withborder";
        if($block->data->withBackground) $classes .= " blockeditor--image-withbackground";
        if($block->data->stretched) $classes .= " blockeditor--image-stretched";
        $url = $block->data->url;
        $mime = $block->data->mime;
        $width = $block->data->width;
        $height = $block->data->height;
        $accent = $block->data->accent_color;
        $contrast = $block->data->accent_contrast;
        $caption = $block->data->caption;
        return $this->image($classes, $accent, $contrast, $url, $mime, $width, $height, $caption, $block);
    }

    private function __from_imagetool($block) {
        $classes = "blockeditor--content blockeditor--image";
        if($block->data->withBorder) $classes .= " blockeditor--image-withborder";
        if($block->data->withBackground) $classes .= " blockeditor--image-withbackground";
        if($block->data->stretched) $classes .= " blockeditor--image-stretched";
        $url = $block->data->file->url;
        $mime = $block->data->file->mime;
        $width = $block->data->file->width;
        $height = $block->data->file->height;
        $accent = $block->data->file->accent_color;
        $contrast = $block->data->file->accent_contrast;
        $caption = $block->data->caption;
        return $this->image($classes, $accent, $contrast, $url, $mime, $width, $height, $caption, $block);
    }

    private function image($classes, $accent, $contrast, $url, $mime, $width, $height, $caption, $block) {
        $figcaption = "";
        if($caption) $figcaption = "<figcaption>" .$block->data->caption. "</figcaption>";
        $html = "<figure class=\"$classes\" style=\"--accent-color: $accent; --accent-contrast: $contrast;\"><img src=\"$url\" type=\"$mime\" width=\"$width\" height=\"$height\" alt=\"".$block->data->caption."\" loading=\"lazy\">$figcaption</figure>";
        return $html;
    }

    private function __from_linktool($block) {
        return view("/pages/landing/block-elements/linktool.html", ['block' => $block]);
    }

    private function __from_quote($block) {
        return view("/pages/landing/block-elements/quote.php", ['block' => $block]);
    }

    private function __from_rawtool($block) {
        return view("/pages/landing/block-elements/rawtool.html", ['block' => $block]);
    }

    private function __from_nestedlist($block) {
        $tag = ($block->data->style === "ordered") ? "ol" : "ul";
        $html = "";
        $html .= $this->__process_list_item($block->data->items, $tag);
        return $html;
    }

    private function __process_list_item($list, $tag) {
        $html = "<$tag class=\"blockeditor--content blockeditor--nestedlist\">";
        foreach($list as $li) {
            $html .= "<li>$li->content";
            if($li->items) $html .= $this->__process_list_item($li->items, $tag);
            $html .= "</li>";
        }
        return $html . "</$tag>";
    }

    private function __from_table($block) {
        $headings = $block->data->withHeadings;
        $html = "<flex-table class=\"blockeditor--content blockeditor--table\">";
        foreach($block->data->content as $column_index => $row) {
            $html .= "<flex-row>";
            $tag = "flex-cell";
            if($column_index === 0 && $headings) $tag = "flex-header";
            foreach($row as $cell) {
                $html .= "<$tag>$cell</$tag>";
            }
            $html .= "</flex-row>";
        }
        return $html . "</flex-table>";
    }

    private function __from_embed($block) {
        return view("/pages/landing/block-elements/embed.html", ['block' => $block]);
    }

    private function __from_codetool($block) {
        return view("/pages/landing/block-elements/code.html", ['block' => $block]);
    }

    private function __from_blockbutton($block) {
        return "<p class=\"blockeditor--content blockeditor--blockbutton\"><a href=\"".$block->data->url."\" class=\"button\">".$block->data->label."</a></p>";
    }

    private function __from_reply($block) {
        $link_title = parse_url($block->data->to_url, PHP_URL_HOST);
        $parsedown = new ParsedownExtra();
        if($block->data->to_quote) $link_title = "<div class=\"attribution\">Quote from $link_title:</div>".$parsedown->parse($block->data->to_quote);
        else if($block->data->to_url) $link_title = "In response to $link_title";
        
        $paragraph = $parsedown->parse($block->data->response);
        /** @var UserIdResult */
        if(isset($this->__reference)) $author = $this->__reference->author->get_hcard("p-author");
        return "<div class=\"h-entry blockeditor--content blockeditor--reply\">
            <a href=\"".$block->data->to_url."\" class=\"u-in-reply-to\">
            <blockquote>$link_title</blockquote>
            </a>
            $author
            <div class=\"p-name p-content\">$paragraph</div>
        </div>";
    }

    private function __from_like($block) {
        $link_title = parse_url($block->data->to_url);
        $link_title = "$link_title[host]$link_title[path]";
        if($block->data->label) $link_title = $block->data->label;
        /** @var UserIdResult */
        if(isset($this->__reference)) $author = $this->__reference->author->get_hcard("p-author");
        return "
            <div class=\"h-entry blockeditor--content blockeditor--like\">
                $author <i name=\"heart\"></i> 
                <a href=\"".$block->data->to_url."\" class=\"u-like-of\">$link_title</a>
            </div>
        ";
    }
}