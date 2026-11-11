<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Demo articles. Titles and bodies are sample newsroom content so the
     * workspace, the public site and the analytics charts have real data.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@newspulse.test')->firstOrFail();
        $editor = User::where('email', 'editor@newspulse.test')->firstOrFail();
        $arif = User::where('email', 'author@newspulse.test')->firstOrFail();
        $farhana = User::where('email', 'farhana@newspulse.test')->firstOrFail();
        $reader = User::where('email', 'reader@newspulse.test')->firstOrFail();

        $categories = Category::all()->keyBy('name');
        $tags = Tag::all();

        $items = [
            [
                'title' => 'Coastal cities unveil joint plan to tackle rising sea levels',
                'category' => 'World', 'author' => $admin, 'featured' => true, 'breaking' => false,
                'tags' => ['Climate', 'Policy'],
                'body' => $this->body('city governments from twelve coastal metros published a shared adaptation roadmap'),
            ],
            [
                'title' => 'Parliament passes sweeping digital privacy act after months of debate',
                'category' => 'Politics', 'author' => $editor, 'featured' => false, 'breaking' => true,
                'tags' => ['Policy'],
                'body' => $this->body('lawmakers approved the Digital Privacy and Consent Act by a comfortable majority'),
            ],
            [
                'title' => 'Central bank holds rates steady, signals patience on inflation',
                'category' => 'Business', 'author' => $editor, 'featured' => false, 'breaking' => false,
                'tags' => ['Markets'],
                'body' => $this->body('the central bank left its benchmark rate unchanged for a third straight meeting'),
            ],
            [
                'title' => 'Repairable modular phones gain momentum with consumers',
                'category' => 'Technology', 'author' => $arif, 'featured' => true, 'breaking' => false,
                'tags' => ['Robotics', 'Research'],
                'body' => $this->body('a new wave of repairable modular phones passed one million pre-orders this quarter'),
            ],
            [
                'title' => 'Historic series win seals the championship with a match to spare',
                'category' => 'Sports', 'author' => $farhana, 'featured' => false, 'breaking' => true,
                'tags' => ['Cricket'],
                'body' => $this->body('a composed fourth-innings chase carried the visitors to their first series win on this ground in two decades'),
            ],
            [
                'title' => 'Streaming platforms bet big on regional-language originals',
                'category' => 'Entertainment', 'author' => $farhana, 'featured' => false, 'breaking' => false,
                'tags' => ['Streaming', 'Music'],
                'body' => $this->body('three major streaming services announced slates of original films and series in regional languages'),
            ],
            [
                'title' => 'New study links regular walking to sharper memory in older adults',
                'category' => 'Health', 'author' => $arif, 'featured' => false, 'breaking' => false,
                'tags' => ['Research'],
                'body' => $this->body('researchers tracked more than four thousand adults over five years and found measurable cognitive benefits'),
            ],
            [
                'title' => 'Universities expand flexible degree paths as enrolment shifts',
                'category' => 'Education', 'author' => $editor, 'featured' => false, 'breaking' => false,
                'tags' => ['Policy'],
                'body' => $this->body('a coalition of public universities rolled out stackable certificate and degree programmes'),
            ],
            [
                'title' => 'Falcon Heavy launch delivers twin climate satellites to orbit',
                'category' => 'Technology', 'author' => $arif, 'featured' => false, 'breaking' => false,
                'tags' => ['Space', 'Climate'],
                'body' => $this->body('the heavy-lift rocket lifted off at dawn and deployed its payloads into a polar orbit'),
            ],
            [
                'title' => 'Small businesses embrace digital payments as cash use declines',
                'category' => 'Business', 'author' => $admin, 'featured' => false, 'breaking' => false,
                'tags' => ['Markets', 'Startups'],
                'body' => $this->body('digital wallets overtook cash for the first time in point-of-sale transactions'),
            ],
            [
                'title' => 'Restoration brings a century-old theatre back to life downtown',
                'category' => 'Entertainment', 'author' => $farhana, 'featured' => false, 'breaking' => false,
                'tags' => ['Music'],
                'body' => $this->body('the gilded auditorium reopened its doors after a five-year restoration effort'),
            ],
            [
                'title' => 'National football squad announces fresh faces for the qualifiers',
                'category' => 'Sports', 'author' => $farhana, 'featured' => false, 'breaking' => false,
                'tags' => ['Football'],
                'body' => $this->body('the head coach named four uncapped players in a twenty-three-man squad'),
            ],
            [
                'title' => 'Electric ferries to connect river districts under new transit plan',
                'category' => 'World', 'author' => $admin, 'featured' => false, 'breaking' => false,
                'tags' => ['Climate', 'Policy'],
                'body' => $this->body('the transport authority ordered a fleet of battery-electric ferries'),
            ],
            [
                'title' => 'Farmers turn to drone mapping to fight crop disease early',
                'category' => 'Business', 'author' => $arif, 'featured' => false, 'breaking' => false,
                'tags' => ['Startups'],
                'body' => $this->body('agritech startups are renting out drones and analytics dashboards to smallholder farms'),
            ],
            [
                'title' => 'Mobile gaming studio crosses fifty million players worldwide',
                'category' => 'Technology', 'author' => $arif, 'featured' => false, 'breaking' => false,
                'tags' => ['Gaming', 'Mobile'],
                'body' => $this->body('the twelve-person studio behind a breakout puzzle hit says organic growth did the heavy lifting'),
            ],
            [
                'title' => 'Bangla edition: রাজধানীতে নতুন সাইকেল লেন উদ্বোধন',
                'category' => 'Politics', 'author' => $editor, 'featured' => false, 'breaking' => false,
                'tags' => ['Policy'],
                'language' => 'bn',
                'body' => $this->banglaBody(),
            ],
            [
                'title' => 'Bangla edition: প্রযুক্তি খাতে বিনিয়োগ বাড়ছে দ্বিগুণ হারে',
                'category' => 'Business', 'author' => $arif, 'featured' => false, 'breaking' => false,
                'tags' => ['Startups', 'Markets'],
                'language' => 'bn',
                'body' => $this->banglaBody(),
            ],
            [
                'title' => 'Bangla edition: জাতীয় ক্রিকেট দলের নতুন কোচ ঘোষণা',
                'category' => 'Sports', 'author' => $farhana, 'featured' => false, 'breaking' => false,
                'tags' => ['Cricket'],
                'language' => 'bn',
                'body' => $this->banglaBody(),
            ],
        ];

        foreach ($items as $index => $item) {
            $publishedAt = now()->subDays(count($items) - $index)->setTime(9, 30);

            $article = Article::create([
                'user_id' => $item['author']->id,
                'category_id' => $categories[$item['category']]->id,
                'title' => $item['title'],
                'slug' => Str::slug(Str::limit($item['title'], 180, '')),
                'excerpt' => Str::limit(strip_tags($item['body']), 180),
                'body' => $item['body'],
                'language' => $item['language'] ?? 'en',
                'status' => 'published',
                'is_featured' => $item['featured'],
                'is_breaking' => $item['breaking'],
                'views_count' => rand(150, 9000),
                'meta_title' => $item['title'],
                'meta_description' => Str::limit(strip_tags($item['body']), 160),
                'published_at' => $publishedAt,
                'reviewed_by' => $editor->id,
            ]);

            $article->tags()->sync(
                $tags->whereIn('slug', collect($item['tags'])->map(fn ($t) => Str::slug($t))->all())->pluck('id')
            );

            // Daily view history for the dashboard charts.
            for ($day = 0; $day < 30; $day++) {
                $viewDate = $publishedAt->copy()->addDays($day)->startOfDay();

                if ($viewDate->isFuture()) {
                    break;
                }

                DB::table('article_views')->insert([
                    'article_id' => $article->id,
                    'viewed_on' => $viewDate->toDateString(),
                    'count' => rand(5, 400),
                ]);
            }
        }

        // Workflow examples: one draft, one in review, one scheduled.
        Article::create([
            'user_id' => $arif->id,
            'category_id' => $categories['Technology']->id,
            'title' => 'Draft: inside the new battery chemistry promising longer range',
            'slug' => 'draft-inside-the-new-battery-chemistry',
            'excerpt' => 'A look at the lab results behind next-generation batteries.',
            'body' => $this->body('a materials science team reported a breakthrough in energy density'),
            'status' => 'draft',
            'views_count' => 0,
        ]);

        Article::create([
            'user_id' => $farhana->id,
            'category_id' => $categories['Sports']->id,
            'title' => 'In review: the rise of franchise leagues in club football',
            'slug' => 'in-review-the-rise-of-franchise-leagues',
            'excerpt' => 'How private leagues are redrawing the football calendar.',
            'body' => $this->body('club football is going through its biggest structural change in a generation'),
            'status' => 'review',
            'views_count' => 0,
        ]);

        Article::create([
            'user_id' => $admin->id,
            'category_id' => $categories['World']->id,
            'title' => 'Scheduled: global summit on urban air quality kicks off next week',
            'slug' => 'scheduled-global-summit-on-urban-air-quality',
            'excerpt' => 'City leaders will announce air-quality pledges at the summit.',
            'body' => $this->body('delegations from sixty cities will gather for the third Urban Air Summit'),
            'status' => 'scheduled',
            'views_count' => 0,
            'published_at' => now()->addDays(2)->setTime(10, 0),
        ]);

        // Comments on the first article.
        $first = Article::where('slug', 'coastal-cities-unveil-joint-plan-to-tackle-rising-sea-levels')->first();

        $root = Comment::create([
            'article_id' => $first->id,
            'user_id' => $reader->id,
            'body' => 'Great reporting - the section on flood insurance was really useful. Any follow-up planned on who pays for the seawalls?',
            'status' => 'approved',
        ]);

        Comment::create([
            'article_id' => $first->id,
            'user_id' => $arif->id,
            'parent_id' => $root->id,
            'body' => 'Thanks! A deeper piece on financing is in the works for next month.',
            'status' => 'approved',
        ]);

        Comment::create([
            'article_id' => $first->id,
            'user_id' => $reader->id,
            'body' => 'This comment is waiting in the moderation queue as a demo.',
            'status' => 'pending',
        ]);

        $reader->bookmarks()->attach($first->id);
    }

    private function body(string $lead): string
    {
        return <<<HTML
        <p>{$lead}, a move officials described as the most coordinated effort of its kind.</p>
        <p>The announcement capped two years of closed-door negotiations and independent reviews. Analysts say the practical effects will be felt gradually, but the direction of travel is clear.</p>
        <h2>What the plan actually says</h2>
        <p>The document runs to ninety pages and touches on funding, transparency and shared technical standards. Its centrepiece is a rolling five-year review cycle that commits participants to publish progress reports annually.</p>
        <blockquote><p>This is the first time so many parties have put measurable targets on paper at the same time.</p></blockquote>
        <p>Critics acknowledge the ambition while questioning enforcement. "Good frameworks fail without consequences," one observer noted, pointing to earlier agreements that lacked teeth.</p>
        <p>Officials expect the first review round to begin within eighteen months. In the meantime, working groups will meet quarterly to align technical details and share interim data with the public.</p>
        HTML;
    }

    private function banglaBody(): string
    {
        return <<<HTML
        <p>সংশ্লিষ্ট কর্তৃপক্ষ এই উদ্যোগকে যুগান্তকারী বলে অভিহিত করেছে এবং জানিয়েছে যে বাস্তবায়ন প্রক্রিয়া ইতোমধ্যেই শুরু হয়েছে।</p>
        <p>দীর্ঘ আলোচনার পর এই সিদ্ধান্ত নেওয়া হয়েছে বলে জানিয়েছেন সংশ্লিষ্ট কর্মকর্তারা। পরিকল্পনাটি বাস্তবায়নে প্রাথমিক পর্যায়ে কিছু চ্যালেঞ্জ মোকাবেলা করতে হতে পারে বলে মত দিয়েছেন বিশ্লেষকরা।</p>
        <h2>মূল বৈশিষ্ট্য</h2>
        <p>পরিকল্পনার মূল অংশে রয়েছে স্বচ্ছতা, নিয়মিত পর্যালোচনা এবং জবাবদিহিতা। প্রতি বছর অগ্রগতি প্রতিবেদন প্রকাশের ব্যবস্থা রাখা হয়েছে।</p>
        <blockquote><p>এই ধরনের সমন্বিত উদ্যোগ আগে কখনো দেখা যায়নি বলে মনে করছেন বিশেষজ্ঞরা।</p></blockquote>
        <p>আগামী কয়েক মাসে বাস্তবায়নের প্রাথমিক ধাপগুলো শুরু হবে। সংশ্লিষ্ট সব পক্ষ মাঠ পর্যায়ে কাজ শুরু করার প্রস্তুতি নিচ্ছে।</p>
        HTML;
    }
}
