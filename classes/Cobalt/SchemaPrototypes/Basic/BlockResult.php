<?php

namespace Cobalt\SchemaPrototypes\Basic;

use Cobalt\SchemaPrototypes\SchemaResult;
use Cobalt\SchemaPrototypes\Traits\Fieldable;
use Cobalt\SchemaPrototypes\Traits\Prototype;


class BlockResult extends SchemaResult {
    use Fieldable;
    protected $type = "block-editor";
    protected bool $asHTML = true;

    #[Prototype]
    protected function field(string $class = "", array $misc = [], string $tag = "block-editor"):string {
        // return $this->input($class, $misc, "block-editor");
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
        foreach($this->value->blocks as $val => $details) {
            if($details->type === "paragraph") return $details->data->text;
        }
        return "";
    }

    function filter($value) {
        return $value;
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
                default:
                    $html .= "<pre>Cannot render type: $block->type</pre>";
                    break;
            }
        }
        return $html;
    }

    private function __from_paragraph($block) {
        return "<p class=\"blockeditor--content blockeditor--paragraph\">" . $block->data->text . "</p>";
    }

    private function __from_header($block) {
        $tag = "h". $block->data->level;
        return "<$tag id=\"$block->id\" class=\"blockeditor--content blockeditor--header\">" . $block->data->text . "</$tag>";
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
        $figcaption = "";
        if($block->data->caption) $figcaption = "<figcaption>" .$block->data->caption. "</figcaption>";
        $html = "<figure class=\"$classes\" style=\"--accent-color: $accent; --accent-contrast: $contrast;\"><img src=\"$url\" type=\"$mime\" width=\"$width\" height=\"$height\" alt=\"".$block->data->caption."\">$figcaption</figure>";
        return $html;
    }

    private function __from_linktool($block) {
        return view("/pages/landing/block-elements/linktool.html", ['block' => $block]);
    }

    private function __from_quote($block) {
        return view("/pages/landing/block-elements/quote.html", ['block' => $block]);
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
}