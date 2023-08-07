<?php

namespace Lightszentip\LaravelReleaseChangelogGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Lightszentip\LaravelReleaseChangelogGenerator\Util\FileHandler;
use Lightszentip\LaravelReleaseChangelogGenerator\Util\VersionUtil;
use Symfony\Component\Console\Command\Command as CommandAlias;

class ReleaseChangelog extends Command
{
    private static string $ar_name = 'releasename';

    private static string $ar_type = 'type';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'changelog:release {--rn|releasename= : Name of release} {--t|type=patch : Which update the current version - patch, minor, major, rc, timestamp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Release version in file';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!file_exists($this->path())) {
            File::put($this->path(), '');
        }
        try {
            $type = trim($this->getArgument(ReleaseChangelog::$ar_type));
            $name = trim($this->getArgument(ReleaseChangelog::$ar_name));

            if ($type != 'rc' && $type != 'patch' && $type != 'minor' && $type != 'major' && $type != 'timestamp') {
                $this->error('Please use timestamp,rc, patch, minor or major for a release');
                return CommandAlias::FAILURE;
            }

            $jsonString = file_get_contents($this->path());
            $decoded_json = json_decode($jsonString);
            if ($decoded_json == null || !property_exists($decoded_json, 'unreleased')) {
                $this->error('No release changelog exists to update');

                return CommandAlias::FAILURE;
            } else {
                VersionUtil::updateVersionByType($type);
                $decoded_json = VersionUtil::generateChangelogWithNewVersion($decoded_json, $name);
                file_put_contents(FileHandler::pathChangelog(), json_encode($decoded_json));
                return self::SUCCESS;
            }
        } catch (\InvalidArgumentException $e) {
            return self::FAILURE;
        } catch (\Exception $e2) {
            $this->error("Error:  $e2 ");

            return self::INVALID;
        }
    }

    private function path(): string
    {
        return Config::get('releasechangelog.path') . DIRECTORY_SEPARATOR . '.changes' . DIRECTORY_SEPARATOR . 'changelog.json';
    }

    private function getArgument(string $key): string
    {
        $result = $this->option($key);

        if (is_null($result)) {
            $result = $this->ask('What is ' . $key . ' ?');
        }

        if ($result == null) {
            $this->error("No input for key:  $key ");
            throw new \InvalidArgumentException();
        }

        return $result;
    }
}
