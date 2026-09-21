<?php
declare(strict_types=1);

namespace CoreStore\Engine;

class ThemeEngine
{
   private array $settings;

   public function __construct(array $settings)
   {
       $this->settings = $settings;
   }

   public function renderCssCustomProperties(): string
   {
       $t = $this->settings['theme'] ?? [];
       $x = $this->settings['card_text'] ?? [];
       $l = $this->settings['layout'] ?? [];
       $h = $this->settings['header'] ?? [];

       return <<<CSS
       :root {
           --primary-accent: {$t['primary_accent']};
           --secondary-accent: {$t['secondary_accent']};
           --bg-color: {$t['background_color']};
           --card-bg: {$t['card_background']};
           --font-heading: {$t['font_family_heading']};
           --font-body: {$t['font_family_body']};
           --font-subheading: {$t['font_family_subheading']};
           --card-radius: {$t['border_radius']};
           --grid-cols: {$l['grid_columns_desktop']};
           --grid-gap: {$l['grid_gap']};
           --card-text-align: {$x['alignment']};
           --order-badge: {$x['order_badge']};
           --order-title: {$x['order_title']};
           --order-desc: {$x['order_description']};
           --order-price: {$x['order_price']};
           --order-action: {$x['order_action']};
           
           /* Header Settings */
           --header-bg: {$h['background_color']};
           --header-text: {$h['text_color']};
       }
CSS;
   }

   public function getCardClasses(): string
   {
       $placement = $this->settings['card_text']['placement'] ?? 'stacked_below';
       $entryAnim = $this->settings['animations']['entry_animation'] ?? 'none';
       $hoverAnim = $this->settings['animations']['hover_effect'] ?? 'none';

       $classes = ["card-root", "layout-{$placement}"];
       if ($entryAnim !== 'none') $classes[] = "anim-entry entry-{$entryAnim}";
       if ($hoverAnim !== 'none' && $hoverAnim !== 'tilt_3d') $classes[] = "hover-{$hoverAnim}";

       return implode(' ', $classes);
   }
}
