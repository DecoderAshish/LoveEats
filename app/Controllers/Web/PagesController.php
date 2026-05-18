<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Bootstrap\App;
use App\Http\Request;
use App\Http\Response;
use App\Support\View;

final class PagesController
{
    /** @param array<string, mixed> $params */
    private function page(App $app, string $view, array $data, int $status = 200): Response
    {
        $content = View::render('pages/' . $view, $data, $app->rootPath());
        $html = View::render('layouts/app', array_merge($data, ['content' => $content]), $app->rootPath());
        return Response::html($html, $status);
    }

    public function landing(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'landing', [
            'title' => 'Love Eats — Bold cravings, warm delivery',
            'description' => 'Premium food delivery with reels, wallet cashback, and smart recommendations.',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function home(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'home', [
            'title' => 'Home — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function restaurants(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'restaurants', [
            'title' => 'Restaurants — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function restaurantDetails(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'restaurant_details', [
            'title' => 'Restaurant — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'slug' => (string)($params['restaurant_slug'] ?? ''),
        ]);
    }

    public function reels(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'reels', [
            'title' => 'Reels — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function cart(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'cart', [
            'title' => 'Cart — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function checkout(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'checkout', [
            'title' => 'Checkout — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function login(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'login', [
            'title' => 'Login — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
        ]);
    }

    public function orderSuccess(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'order_success', [
            'title' => 'Order Success — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'orderPublicId' => (string)($params['order_public_id'] ?? ''),
        ]);
    }

    public function tracking(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'tracking', [
            'title' => 'Live Tracking — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'orderPublicId' => (string)($params['order_public_id'] ?? ''),
        ]);
    }

    public function wallet(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Wallet — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Wallet',
        ]);
    }

    public function referrals(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Referrals — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Referral Dashboard',
        ]);
    }

    public function coupons(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Coupons — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Coupons',
        ]);
    }

    public function profile(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Profile — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Profile',
        ]);
    }

    public function addresses(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Addresses — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Address Management',
        ]);
    }

    public function notifications(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Notifications — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Notifications',
        ]);
    }

    public function orders(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Orders — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Order History',
        ]);
    }

    public function favorites(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Favorites — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Favorites',
        ]);
    }

    public function support(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Support — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Support Chat',
        ]);
    }

    public function pickup(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Pickup — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Pickup Orders',
        ]);
    }

    public function about(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'About — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'About Us',
        ]);
    }

    public function contact(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Contact — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Contact Us',
        ]);
    }

    public function privacy(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Privacy — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Privacy Policy',
        ]);
    }

    public function terms(Request $request, App $app, array $params): Response
    {
        return $this->page($app, 'placeholder', [
            'title' => 'Terms — Love Eats',
            'appName' => (string)$app->config()->get('app.name'),
            'pageName' => 'Terms',
        ]);
    }
}
