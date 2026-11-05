<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Page;
use App\Services\MenuService;
use Illuminate\Database\Seeder;

/**
 * Starter navigation. Runs only when a menu location has no items yet.
 * Item format: [label, url, children?]. "page:slug" links to a CMS page.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'header' => ['Main navigation', [
                ['Home', '/'],
                ['About Us', 'page:about'],
                ['Destinations', '/destinations', [
                    ['All Destinations', '/destinations'],
                    ['Study in India', '/study-in-india'],
                    ['Study in Dubai (UAE)', '/study-in-uae'],
                    ['Study in Australia', '/study-in-australia'],
                ]],
                ['Universities', '/universities', [
                    ['All Universities', '/universities'],
                    ['Find a Course', '/courses'],
                    ['Scholarships', 'page:scholarships'],
                ]],
                ['Services', '/services'],
                ['Success Stories', '/success-stories'],
                ['Gallery', '/gallery'],
                ['Blog', '/blog'],
                ['Contact', '/contact'],
            ]],
            'footer_quick' => ['Quick Links', [
                ['About Us', 'page:about'], ['Destinations', '/destinations'], ['Universities', '/universities'],
                ['Services', '/services'], ['Success Stories', '/success-stories'], ['Gallery', '/gallery'],
                ['Blog', '/blog'], ['Contact Us', '/contact'],
            ]],
            'footer_services' => ['Our Services', [
                ['Recruitment & Admission', '/services/student-recruitment-admission'],
                ['NID & Birth Certificate', '/services/nid-birth-certificate-assistance'],
                ['Passport Assistance', '/services/passport-assistance'],
                ['Student Visa Assistance', '/services/student-visa-assistance'],
                ['Global Airline Ticketing', '/services/airline-ticketing'],
                ['Scholarships', 'page:scholarships'],
            ]],
            'footer_legal' => ['Legal', [
                ['Privacy Policy', 'page:privacy-policy'],
                ['Terms & Conditions', 'page:terms-and-conditions'],
                ['Sitemap', '/sitemap.xml'],
            ]],
        ];

        foreach ($menus as $location => [$name, $items]) {
            $menu = Menu::query()->firstOrCreate(['location' => $location], ['name' => $name]);
            if ($menu->items()->exists()) {
                continue;
            }
            $this->createItems($menu, $items);
        }

        app(MenuService::class)->flush();
    }

    private function createItems(Menu $menu, array $items, ?int $parentId = null): void
    {
        foreach ($items as $i => $item) {
            [$label, $url] = $item;
            $data = ['label' => $label, 'parent_id' => $parentId, 'sort_order' => $i, 'link_type' => 'internal', 'url' => $url];

            if (str_starts_with($url, 'page:')) {
                $data['link_type'] = 'page';
                $data['page_id'] = Page::query()->where('slug', substr($url, 5))->value('id');
                $data['url'] = null;
            }

            $created = $menu->items()->create($data);

            if (! empty($item[2])) {
                $this->createItems($menu, $item[2], $created->id);
            }
        }
    }
}
