<?php
declare(strict_types=1);

namespace CoreStore\Engine;

class CardRenderer
{
   private ThemeEngine $themeEngine;

   public function __construct(ThemeEngine $themeEngine)
   {
       $this->themeEngine = $themeEngine;
   }

   public function render(array $item, int $index = 0): string
   {
       $classes = $this->themeEngine->getCardClasses();
       $title = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
       $desc = htmlspecialchars($item['description'] ?? '', ENT_QUOTES, 'UTF-8');
       $price = htmlspecialchars($item['price'] ?? '', ENT_QUOTES, 'UTF-8');
       $badge = htmlspecialchars($item['badge'] ?? '', ENT_QUOTES, 'UTF-8');
       $image = htmlspecialchars($item['image'] ?? 'assets/placeholder.jpg', ENT_QUOTES, 'UTF-8');

       $badgeHtml = $badge ? "<span class=\"card-el-badge\">{$badge}</span>" : '';

       return <<<HTML
       <article class="{$classes}" data-index="{$index}">
           <div class="card-media-wrapper">
               <img src="{$image}" alt="{$title}" loading="lazy" class="card-img">
           </div>
           <div class="card-content-stack">
               {$badgeHtml}
               <h3 class="card-el-title">{$title}</h3>
               <p class="card-el-desc">{$desc}</p>
               <div class="card-el-price">{$price}</div>
               <div class="card-el-action">
                   <button type="button" class="btn-action">Order Now</button>
               </div>
           </div>
       </article>
HTML;
   }
}
