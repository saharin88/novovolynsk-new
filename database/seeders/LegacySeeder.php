<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class LegacySeeder extends Seeder
{
    use WithoutModelEvents;

    private const string LEGACY_CONNECTION_NAME = 'legacy_import';

    private const string LEGACY_HOST = 'localhost';

    private const int LEGACY_PORT = 3306;

    private const string LEGACY_USER = 'root';

    private const string LEGACY_PASSWORD = 'root';

    private const string LEGACY_DATABASE = 'nov';

    private const string LEGACY_TABLE_PREFIX = 'kgbwt_';

    private const string LEGACY_CATEGORIES_TABLE = 'categories';

    private const string LEGACY_ITEMS_TABLE = 'aboard';

    private const string LEGACY_USERS_TABLE = 'users';

    private const string LEGACY_COMPONENT_EXTENSION = 'com_aboard';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacyConnection = DB::connection($this->configureLegacyConnection());

        $legacyUserIds = $this->collectLegacyUserIds($legacyConnection);
        $legacyCategoryIds = $this->collectLegacyCategoryIds($legacyConnection);

        if ($legacyUserIds === []) {
            $this->command->warn('No legacy users with announcements were found.');

            return;
        }

        if ($legacyCategoryIds === []) {
            $this->command->warn('No legacy categories with announcements were found.');

            return;
        }

        $userMap = $this->importUsers($legacyConnection, $legacyUserIds);

        if ($userMap === []) {
            $this->command->warn('No users were imported from legacy database.');

            return;
        }

        $categoryMap = $this->importCategories($legacyConnection, $legacyCategoryIds);

        if ($categoryMap === []) {
            $this->command->warn('No categories were imported from legacy database.');

            return;
        }

        $importedItems = $this->importItems($legacyConnection, $categoryMap, $userMap);

        $this->command->info(sprintf(
            'Legacy import complete: %d users, %d categories and %d items processed.',
            count($userMap),
            count($categoryMap),
            $importedItems,
        ));
    }

    private function configureLegacyConnection(): string
    {
        config([
            'database.connections.'.self::LEGACY_CONNECTION_NAME => [
                'driver' => 'mysql',
                'host' => self::LEGACY_HOST,
                'port' => self::LEGACY_PORT,
                'database' => self::LEGACY_DATABASE,
                'username' => self::LEGACY_USER,
                'password' => self::LEGACY_PASSWORD,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null,
            ],
        ]);

        DB::purge(self::LEGACY_CONNECTION_NAME);

        return self::LEGACY_CONNECTION_NAME;
    }

    /**
     * @return list<int>
     */
    private function collectLegacyUserIds(ConnectionInterface $legacyConnection): array
    {
        $table = $this->resolveLegacyTableName(self::LEGACY_ITEMS_TABLE);
        $legacyUserIds = [];

        foreach ($legacyConnection->table($table)->whereNotNull('uid')->distinct()->orderBy('uid')->pluck('uid') as $legacyUserId) {
            $userId = (int) $legacyUserId;

            if ($userId <= 0) {
                continue;
            }

            $legacyUserIds[] = $userId;
        }

        return array_values(array_unique($legacyUserIds));
    }

    /**
     * @return list<int>
     */
    private function collectLegacyCategoryIds(ConnectionInterface $legacyConnection): array
    {
        $table = $this->resolveLegacyTableName(self::LEGACY_ITEMS_TABLE);
        $legacyCategoryIds = [];

        foreach ($legacyConnection->table($table)->whereNotNull('catid')->distinct()->orderBy('catid')->pluck('catid') as $legacyCategoryId) {
            $categoryId = (int) $legacyCategoryId;

            if ($categoryId <= 0) {
                continue;
            }

            $legacyCategoryIds[] = $categoryId;
        }

        return array_values(array_unique($legacyCategoryIds));
    }

    /**
     * @param  list<int>  $legacyUserIds
     * @return array<int, int>
     */
    private function importUsers(ConnectionInterface $legacyConnection, array $legacyUserIds): array
    {
        if (empty($legacyUserIds)) {
            return [];
        }

        /** @var array<int, array{name: string, email: string, password: string}> $legacyUsers */
        $legacyUsers = $legacyConnection
            ->table($this->resolveLegacyTableName(self::LEGACY_USERS_TABLE))
            ->whereIn('id', $legacyUserIds)
            ->get(['id', 'name', 'email', 'password'])
            ->reduce(function (array $carry, $user) {
                if (filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    $carry[$user->id] = [
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $user->password,
                        //                        'created_at' => now(),
                        //                        'updated_at' => now(),
                    ];
                }

                return $carry;
            }, []);

        $emails = array_column($legacyUsers, 'email');

        foreach (array_chunk($legacyUsers, 500) as $chunk) {
            User::query()->insertOrIgnore($chunk);
        }

        $allUsers = User::query()
            ->whereIn('email', $emails)
            ->pluck('id', 'email')
            ->toArray();

        $localUserMap = [];

        foreach ($legacyUsers as $legacyUserId => $legacyUser) {
            if (! isset($allUsers[$legacyUser['email']])) {
                continue;
            }

            $localUserMap[$legacyUserId] = $allUsers[$legacyUser['email']];
        }

        return $localUserMap;
    }

    /**
     * @param  list<int>  $legacyCategoryIds
     * @return array<int, int>
     */
    private function importCategories(ConnectionInterface $legacyConnection, array $legacyCategoryIds): array
    {
        if (empty($legacyCategoryIds)) {
            return [];
        }

        $table = $this->resolveLegacyTableName(self::LEGACY_CATEGORIES_TABLE);

        $legacyCategories = $legacyConnection
            ->table($table)
            ->whereIn('id', $legacyCategoryIds)
            ->where('extension', self::LEGACY_COMPONENT_EXTENSION)
            ->where('published', 1)
            ->pluck('title', 'id')
            ->toArray();

        $names = array_unique(array_values($legacyCategories));

        Category::query()->insertOrIgnore(
            array_map(static fn (string $name): array => [
                'name' => $name,
                //                'created_at' => now(),
                //                'updated_at' => now(),
            ], $names)
        );

        $existingCategories = Category::query()
            ->withTrashed()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->toArray();

        $categoryMap = [];

        foreach ($legacyCategories as $legacyId => $name) {
            if (! isset($existingCategories[$name])) {
                continue;
            }

            $categoryMap[$legacyId] = $existingCategories[$name];
        }

        return $categoryMap;
    }

    /**
     * @param  array<int, int>  $categoryMap
     * @param  array<int, int>  $userMap
     */
    private function importItems(ConnectionInterface $legacyConnection, array $categoryMap, array $userMap): int
    {
        $table = $this->resolveLegacyTableName(self::LEGACY_ITEMS_TABLE);
        $importedItems = 0;

        $legacyConnection
            ->table($table)
            ->select([
                'id',
                'uid',
                'catid',
                'created',
                'updated',
                'title',
                'text',
                'phone',
                'views',
                'phoneviews',
                'emailviews',
                'state',
                'price',
                'currency',
                'name',
            ])
            ->whereIn('uid', array_keys($userMap))
            ->whereIn('catid', array_keys($categoryMap))
            ->orderBy('id')
            ->chunk(250, function ($legacyItems) use (&$importedItems, $categoryMap, $userMap): void {
                $insertBuffer = [];

                foreach ($legacyItems as $legacyItem) {
                    $categoryId = $categoryMap[(int) $legacyItem->catid] ?? null;
                    $localUserId = $userMap[(int) $legacyItem->uid] ?? null;

                    if ($categoryId === null || $localUserId === null) {
                        continue;
                    }

                    $title = trim((string) $legacyItem->title);

                    if ($title === '') {
                        $title = 'Legacy item #'.(int) $legacyItem->id;
                    }

                    $createdAt = $this->normalizeLegacyDate((string) $legacyItem->created) ?? now();
                    $updatedAt = $this->normalizeLegacyDate((string) $legacyItem->updated) ?? $createdAt;
                    $state = (int) $legacyItem->state;

                    $contacts = $this->parseContacts(
                        $legacyItem->phone,
                        $legacyItem->name,
                    );

                    $itemData = [
                        'user_id' => $localUserId,
                        'category_id' => $categoryId,
                        'title' => Str::limit($title, 255, ''),
                        'created_at' => $createdAt->toDateTimeString(),
                        'body' => trim((string) $legacyItem->text) ?: $title,
                        'contacts' => $this->encodeContacts($contacts),
                        'price' => $this->normalizePrice($legacyItem->price),
                        'currency' => $this->mapCurrency($legacyItem->currency),
                        'views' => max(0, (int) $legacyItem->views),
                        'phone_views' => max(0, (int) $legacyItem->phoneviews),
                        'email_views' => max(0, (int) $legacyItem->emailviews),
                        'archived_at' => $this->resolveArchivedAt($state, $updatedAt)?->toDateTimeString(),
                        'deleted_at' => $this->resolveDeletedAt($state, $updatedAt)?->toDateTimeString(),
                        'updated_at' => $updatedAt->toDateTimeString(),
                    ];

                    $insertBuffer[] = $itemData;
                }

                if (! empty($insertBuffer)) {
                    Item::query()->insert($insertBuffer);
                    $importedItems += count($insertBuffer);
                }
            });

        return $importedItems;
    }

    /**
     * @return array<int, array{name: string, phone: string}>|null
     */
    private function parseContacts(string $legacyPhone, ?string $legacyName): ?array
    {
        $legacyPhone = trim($legacyPhone);

        if ($legacyPhone === '') {
            return null;
        }

        $contactName = trim((string) $legacyName);

        $decodedPhone = json_decode($legacyPhone, true);
        $contacts = [];

        if (is_array($decodedPhone)) {
            foreach ($decodedPhone as $phoneData) {
                if (! is_array($phoneData) || ! array_key_exists('number', $phoneData)) {
                    continue;
                }

                $number = trim((string) $phoneData['number']);

                if ($number === '') {
                    continue;
                }

                $contacts[] = [
                    'name' => $contactName,
                    'phone' => $number,
                ];
            }
        }

        if ($contacts !== []) {
            return $contacts;
        }

        return [
            [
                'name' => $contactName,
                'phone' => $legacyPhone,
            ],
        ];
    }

    private function normalizePrice(mixed $legacyPrice): ?int
    {
        $price = (int) $legacyPrice;

        return $price > 0 ? $price : null;
    }

    /**
     * @param  array<int, array{name: string, phone: string}>|null  $contacts
     */
    private function encodeContacts(?array $contacts): ?string
    {
        if ($contacts === null) {
            return null;
        }

        $encoded = json_encode($contacts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    private function mapCurrency(mixed $legacyCurrency): ?string
    {
        return match ((int) $legacyCurrency) {
            0 => 'UAH',
            1 => 'USD',
            2 => 'EUR',
            3 => 'PLN',
            default => null,
        };
    }

    private function resolveArchivedAt(int $legacyState, CarbonInterface $updatedAt): ?CarbonInterface
    {
        if ($legacyState === 0 || $legacyState === 2) {
            return $updatedAt;
        }

        return null;
    }

    private function resolveDeletedAt(int $legacyState, CarbonInterface $updatedAt): ?CarbonInterface
    {
        return $legacyState === -2 ? $updatedAt : null;
    }

    private function normalizeLegacyDate(string $legacyDate): ?Carbon
    {
        $legacyDate = trim($legacyDate);

        if ($legacyDate === '' || $legacyDate === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($legacyDate);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveLegacyTableName(string $table): string
    {
        return self::LEGACY_TABLE_PREFIX.$table;
    }
}
