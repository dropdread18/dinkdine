<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A private, searchable reference for Henri's own use - not a customer or
 * staff feature. Gated to his email specifically rather than role:admin,
 * since the other admin account on this system (randerexbisquera11@gmail.com)
 * should not see it either; this is his personal price cheat-sheet, not a
 * business feature to share. Deliberately has no nav link anywhere - reach
 * it by URL only.
 *
 * Menu content is hardcoded from "Dink & Dine Pickleball Menu.pdf" rather
 * than stored in the database - it's a short, rarely-changing physical
 * store menu, not something that needs an admin CRUD screen. Ask to have
 * this file edited directly if prices change.
 */
class StoreMenuController extends Controller
{
    private const ALLOWED_EMAIL = 'hjbalbiran@gmail.com';

    /**
     * @return array<int, array{category: string, items: array<int, array{name: string, price: int}>}>
     */
    private function menu(): array
    {
        return [
            [
                'category' => 'Drinks',
                'items' => [
                    ['name' => 'Delight (Big)', 'price' => 70],
                    ['name' => 'Gatorade (Big)', 'price' => 65],
                    ['name' => 'Pocari (Big)', 'price' => 65],
                    ['name' => 'Mogu Mogu', 'price' => 65],
                    ['name' => 'Pocari (Small)', 'price' => 55],
                    ['name' => 'Gatorade (Small)', 'price' => 50],
                    ['name' => 'Del Monte', 'price' => 50],
                    ['name' => 'Fit n Right', 'price' => 50],
                    ['name' => 'Dutch Mill Delight', 'price' => 40],
                    ['name' => 'Calamansi', 'price' => 40],
                    ['name' => 'Mountain Dew', 'price' => 25],
                    ['name' => 'Minute Maid', 'price' => 25],
                    ['name' => 'Softdrinks', 'price' => 20],
                    ['name' => 'C2', 'price' => 20],
                    ['name' => 'Dutch Mill', 'price' => 20],
                    ['name' => 'Chuckie', 'price' => 20],
                    ['name' => 'Yakult', 'price' => 20],
                    ['name' => 'Kopiko', 'price' => 15],
                ],
            ],
            [
                'category' => 'Beer',
                'items' => [
                    ['name' => 'Red Horse', 'price' => 150],
                    ['name' => 'San Miguel Light', 'price' => 70],
                ],
            ],
            [
                'category' => 'Snacks',
                'items' => [
                    ['name' => 'Piatos', 'price' => 50],
                    ['name' => 'Nova', 'price' => 50],
                    ['name' => 'Cheezy', 'price' => 45],
                    ['name' => 'Chippy', 'price' => 40],
                    ['name' => 'Chiz Curls', 'price' => 30],
                    ['name' => 'Inihaw na Bangus Cracker', 'price' => 15],
                    ['name' => 'Candies (3x)', 'price' => 5],
                ],
            ],
        ];
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->user()?->email !== self::ALLOWED_EMAIL) {
            throw new HttpException(404); // 404, not 403 - don't reveal this page exists to anyone else.
        }

        return view('store-menu', ['menu' => $this->menu()]);
    }
}
