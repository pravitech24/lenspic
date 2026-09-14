<?php

namespace App\Http\Controllers;

use App\Models\PlanEntitlement;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MarketingController extends Controller
{
    public function home(): View { return $this->page('home', 'Private group photo delivery, thoughtfully organized', 'LensPic helps photographers and group owners privately organize and deliver group photos through secure galleries and consent-based matching.'); }
    public function features(): View { return $this->page('features', 'Everything You Need to Deliver Group Photos', 'Explore LensPic features for private galleries, consent-based personalization, branding, media delivery and studio collaboration.', ['features' => config('marketing.features')]); }
    public function howItWorks(): View { return $this->page('journey', 'How LensPic Works', 'See the separate Photographer and Group Member flows for private Group setup, uploads, invitations and controlled delivery.', ['journey' => 'how-it-works']); }
    public function photographers(): View { return $this->page('photographers', 'Spend less time sorting. Deliver with more control.', 'LensPic gives photographers a repeatable workflow for client galleries, selections, branding and personalized delivery.'); }
    public function guests(): View { return $this->page('journey', 'Your group photographs, through a private member experience', 'Join an authorized group, browse permitted galleries and optionally use consent-based photo discovery.', ['journey' => 'guests']); }
    public function discovery(): View { return $this->page('journey', 'Find potentially matching group photos with your consent', 'LensPic compares a consented selfie with photographs inside one authorized group gallery.', ['journey' => 'discovery']); }
    public function biometric(): View { return $this->discovery(); }
    public function galleries(): View { return $this->page('journey', 'Group galleries with access and delivery controls', 'Organize covers, folders, invitations, favourites and permitted downloads around each group.', ['journey' => 'galleries']); }
    public function solutions(): View { return $this->page('solutions', 'Built around the way groups actually share photographs', 'Explore LensPic workflows for weddings, corporate gatherings, education, conferences, festivals and private celebrations.', ['solutions' => config('marketing.solutions')]); }

    public function solutionPhotographers(): View { return $this->solutionPage('photographers'); }
    public function solutionWeddings(): View { return $this->solutionPage('weddings'); }
    public function solutionCelebrations(): View { return $this->solutionPage('celebrations'); }
    public function solutionCorporate(): View { return $this->solutionPage('corporate'); }
    public function solutionInstitutions(): View { return $this->solutionPage('institutions'); }
    public function solutionConferences(): View { return $this->solutionPage('conferences'); }

    public function about(): View { return $this->page('about', 'About LensPic', 'LensPic is an intelligent group-photo platform for private, organized and professional photo delivery.'); }
    public function contact(Request $request): View { return $this->page('contact', 'Talk to the LensPic team', 'Book a product demo, ask a sales question or contact support through a secure enquiry form.', ['selectedType' => $request->query('type')]); }

    public function pricing(): View
    {
        $plans = collect(BillingController::planCatalog());
        return $this->page('pricing', 'Plans that grow with your delivery workflow', 'Compare active LensPic plans using the same quota source enforced by the application.', compact('plans'));
    }

    public function blog(Request $request): View
    {
        $posts = $this->publishedPosts();
        $query = mb_strtolower(trim((string) $request->query('q')));
        $category = trim((string) $request->query('category'));
        if ($query !== '') $posts = $posts->filter(fn ($post) => str_contains(mb_strtolower($post['title'].' '.$post['excerpt']), $query));
        if ($category !== '') $posts = $posts->where('category', $category);
        return $this->page('blog', 'Ideas for better group-photo delivery', 'Original LensPic guides for photographers and group owners.', ['posts' => $posts, 'categories' => $this->publishedPosts()->pluck('category')->unique()->values()]);
    }

    public function article(string $slug): View
    {
        $post = $this->publishedPosts()->firstWhere('slug', $slug);
        abort_unless($post, 404);
        return $this->page('article', $post['title'], $post['seo_description'], compact('post'));
    }

    public function faqs(): View { return $this->page('faqs', 'LensPic questions, answered clearly', 'Find practical answers about galleries, invitations, privacy, photographer tools and billing.', ['faqs' => config('marketing.faqs')]); }
    public function help(): View { return $this->page('journey', 'Help and support', 'Find the right LensPic workflow or send a support enquiry.', ['journey' => 'help']); }
    public function join(): View { return $this->page('join', 'Join a LensPic group', 'Use the group link, QR code or six-character code supplied by the group owner.'); }
    public function privacy(): View { return $this->policy('privacy'); }
    public function terms(): View { return $this->policy('terms'); }
    public function refunds(): View { return $this->policy('refunds'); }
    public function cookies(): View { return $this->policy('cookies'); }
    public function security(): View { return $this->policy('security'); }
    public function biometricConsent(): View { return $this->policy('biometric-consent'); }
    public function retention(): View { return $this->policy('retention'); }
    public function deletion(): View { return $this->policy('data-deletion'); }
    public function acceptableUse(): View { return $this->policy('acceptable-use'); }
    public function copyright(): View { return $this->policy('copyright'); }

    public function sitemap()
    {
        $urls = collect(['home','marketing.features','marketing.how-it-works','marketing.photographers','marketing.guests','marketing.discovery','marketing.galleries','marketing.solutions','marketing.about','marketing.contact','pricing','marketing.blog','marketing.faqs','marketing.help','marketing.join','marketing.privacy','marketing.terms','marketing.refunds','marketing.cookies','marketing.security','marketing.biometric-consent','marketing.retention','marketing.deletion','marketing.acceptable-use','marketing.copyright'])->map(fn ($name) => route($name));
        foreach (config('marketing.solutions') as $solution) $urls->push(route($solution['route']));
        foreach ($this->publishedPosts() as $post) $urls->push(route('marketing.blog.article', $post['slug']));
        return response()->view('marketing.sitemap', compact('urls'))->header('Content-Type', 'application/xml');
    }

    public function feed()
    {
        $posts = $this->publishedPosts();
        return response()->view('marketing.feed', compact('posts'))->header('Content-Type', 'application/rss+xml');
    }

    private function page(string $view, string $title, string $description, array $data = []): View
    {
        return view("marketing.{$view}", [...$data, 'title' => $title, 'description' => $description]);
    }

    private function solutionPage(string $key): View
    {
        $data = config("marketing.solutions.{$key}");
        abort_unless($data, 404);
        return $this->page('solution', $data['title'], $data['seo_description'], compact('key', 'data'));
    }

    private function policy(string $key): View
    {
        $policy = config("marketing.policies.{$key}");
        abort_unless($policy, 404);
        return $this->page('legal', $policy['title'], $policy['description'], compact('key', 'policy'));
    }

    private function publishedPosts(): Collection
    {
        return collect(config('marketing.posts'))->map(fn ($post, $slug) => [...$post, 'slug' => $slug])
            ->filter(fn ($post) => $post['published_at'] <= now()->toDateString())->sortByDesc('published_at')->values();
    }
}
