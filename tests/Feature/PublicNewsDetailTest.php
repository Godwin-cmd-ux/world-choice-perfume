<?php

namespace Tests\Feature;

use App\Services\SupabaseService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The public news page and its per-post page.
 *
 * Every card on /news links to /news/{post}, where the whole announcement
 * reads (the list shows an excerpt). Three rules are pinned here: the list
 * really links each card, an unpublished post is unreachable at its own URL —
 * the is_published filter is part of the read, not of the linking — and a
 * non-numeric id never reaches the database. The app reads the same post as
 * JSON off the same controller (GET /api/news/{post}).
 */
class PublicNewsDetailTest extends TestCase
{
    /** The tail of the fixture's content — beyond the list's excerpt. */
    private const TAIL = 'Come celebrate with us on the third of December.';

    protected function setUp(): void
    {
        parent::setUp();

        // SupabaseService's request cache outlives a single test — clear it
        // so an earlier test's row cannot answer this one's read.
        SupabaseService::clearCache();
    }

    public function test_each_card_on_the_news_page_links_to_its_own_page(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/news');

        $response->assertOk();
        // The card links to the post's page…
        $response->assertSee(route('customer.news.show', 5, false));
        // …and shows an excerpt: the tail only exists on the detail page.
        $response->assertSee('Grand Opening of the Mikocheni Branch');
        $response->assertDontSee(self::TAIL);
    }

    public function test_the_detail_page_reads_the_post_whole(): void
    {
        $this->fakeSupabase();

        $response = $this->get('/news/5');

        $response->assertOk();
        $response->assertSee('Grand Opening of the Mikocheni Branch');
        // The full content, both paragraphs, on one page.
        $response->assertSee('A new corner of luxury opens its doors');
        $response->assertSee(self::TAIL);
        // The branch chip and the way back.
        $response->assertSee('Mikocheni');
        $response->assertSee('All news');
    }

    public function test_an_unpublished_post_is_not_readable_at_its_own_url(): void
    {
        $this->fakeSupabase();

        $this->get('/news/8')->assertNotFound();

        // Whatever the id, the read asks for published posts only — the
        // filter is what makes an unpublished id a 404.
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/rest/v1/news_posts')
            && str_contains(urldecode($request->url()), 'is_published=eq.true'));
    }

    public function test_an_unknown_id_is_a_404(): void
    {
        $this->fakeSupabase();

        $this->get('/news/999')->assertNotFound();
    }

    public function test_a_non_numeric_id_never_reaches_the_database(): void
    {
        Http::fake([]);

        $this->get('/news/not-a-post')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_the_app_reads_the_same_post_as_json(): void
    {
        $this->fakeSupabase();

        $response = $this->getJson('/api/news/5');

        $response->assertOk();
        $response->assertJsonPath('post.id', 5);
        $response->assertJsonPath('post.title', 'Grand Opening of the Mikocheni Branch');
        $response->assertJsonPath('post.branch.name', 'Mikocheni');
        // The whole content — the app's detail screen renders it whole too.
        $this->assertStringContainsString(self::TAIL, (string) $response->json('post.content'));
    }

    public function test_the_apps_detail_read_is_404_for_an_unpublished_post(): void
    {
        $this->fakeSupabase();

        $this->getJson('/api/news/8')->assertNotFound();
    }

    /** One published post (id 5) and one unpublished (8), plus the branch join. */
    private function fakeSupabase(): void
    {
        $published = [
            'id' => 5,
            'title' => 'Grand Opening of the Mikocheni Branch',
            // Long enough that the list's 260-character excerpt cannot reach
            // the tail — the two halves prove excerpt vs whole.
            'content' => 'A new corner of luxury opens its doors on Mlimani Street, with every fragrance from the collection on the shelf, a personal scent profiling corner, and a welcome gift for the first hundred guests. Our customer care team will be on the floor all week to help you find the one that suits you best. ' . self::TAIL,
            'branch_id' => 11,
            'author_id' => 9,
            'image_url' => null,
            'is_published' => true,
            'created_at' => '2026-09-20T09:00:00+03:00',
        ];

        Http::fake(function (Request $request) use ($published) {
            $url = urldecode($request->url());

            if (str_contains($url, '/rest/v1/news_posts')) {
                // Ids the read filters out answer no rows — id 8 exists but is
                // unpublished, and the published filter removes it; id 999
                // does not exist at all.
                if (str_contains($url, 'id=eq.8') || str_contains($url, 'id=eq.999')) {
                    return Http::response([], 200);
                }

                return Http::response([$published], 200);
            }

            if (str_contains($url, '/rest/v1/branches')) {
                return Http::response([['id' => 11, 'name' => 'Mikocheni']], 200);
            }

            return Http::response([], 200);
        });
    }
}
