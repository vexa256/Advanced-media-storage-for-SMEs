<?php

declare(strict_types=1);

namespace PackageVersions;

use Composer\InstalledVersions;
use OutOfBoundsException;

/**
 * This class is generated by composer/package-versions-deprecated, specifically by
 * @see \PackageVersions\Installer
 *
 * This file is overwritten at every run of `composer install` or `composer update`.
 *
 * @deprecated in favor of the Composer\InstalledVersions class provided by Composer 2. Require composer-runtime-api:^2 to ensure it is present.
 */
final class Versions
{
    /**
     * @deprecated please use {@see \Composer\InstalledVersions::getRootPackage()} instead. The
     *             equivalent expression for this constant's contents is
     *             `\Composer\InstalledVersions::getRootPackage()['name']`.
     *             This constant will be removed in version 2.0.0.
     */
    const ROOT_PACKAGE_NAME = 'laravel/laravel';

    /**
     * Array of all available composer packages.
     * Dont read this array from your calling code, but use the \PackageVersions\Versions::getVersion() method instead.
     *
     * @var array<string, string>
     * @internal
     */
    const VERSIONS          = array (
  'asm89/stack-cors' => '1.3.0@b9c31def6a83f84b4d4a40d35996d375755f0e08',
  'aws/aws-sdk-php' => '3.146.0@3975a494888375b5bdabf21285f86b47d6e9d531',
  'brick/math' => '0.8.15@9b08d412b9da9455b210459ff71414de7e6241cd',
  'clue/stream-filter' => 'v1.4.1@5a58cc30a8bd6a4eb8f856adf61dd3e013f53f71',
  'composer/package-versions-deprecated' => '1.10.99@dd51b4443d58b34b6d9344cf4c288e621c9a826f',
  'dnoegel/php-xdg-base-dir' => 'v0.1.1@8f8a6e48c5ecb0f991c2fdcf5f154a47d85f9ffd',
  'doctrine/cache' => '1.10.2@13e3381b25847283a91948d04640543941309727',
  'doctrine/dbal' => '2.10.2@aab745e7b6b2de3b47019da81e7225e14dcfdac8',
  'doctrine/event-manager' => '1.1.0@629572819973f13486371cb611386eb17851e85c',
  'doctrine/inflector' => '2.0.3@9cf661f4eb38f7c881cac67c75ea9b00bf97b210',
  'doctrine/lexer' => '1.2.1@e864bbf5904cb8f5bb334f99209b48018522f042',
  'dragonmantank/cron-expression' => 'v2.3.0@72b6fbf76adb3cf5bc0db68559b33d41219aba27',
  'egulias/email-validator' => '2.1.18@cfa3d44471c7f5bfb684ac2b0da7114283d78441',
  'fideloper/proxy' => '4.4.0@9beebf48a1c344ed67c1d36bb1b8709db7c3c1a8',
  'firebase/php-jwt' => 'v5.2.0@feb0e820b8436873675fd3aca04f3728eb2185cb',
  'fruitcake/laravel-cors' => 'v1.0.6@1d127dbec313e2e227d65e0c483765d8d7559bf6',
  'gliterd/backblaze-b2' => '1.4.0@f7b07b2b03af469717b6bb37b1a47c6a85091904',
  'google/apiclient' => 'v2.6.0@326e37fde5145079b74f1ce7249d242739d53cbc',
  'google/apiclient-services' => 'v0.139@84e99f792cae7bd92b8b54c75b0ad3502d628db6',
  'google/auth' => 'v1.10.0@077d6ae98d550161d3b2a0ba283bdce785c74d85',
  'graham-campbell/guzzle-factory' => 'v3.0.4@618cf7220b177c6d9939a36331df937739ffc596',
  'guzzle/guzzle' => 'v3.8.1@4de0618a01b34aa1c8c33a3f13f396dcd3882eba',
  'guzzlehttp/guzzle' => '6.5.5@9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
  'guzzlehttp/promises' => 'v1.3.1@a59da6cf61d80060647ff4d3eb2c03a2bc694646',
  'guzzlehttp/psr7' => '1.6.1@239400de7a173fe9901b9ac7c06497751f00727a',
  'http-interop/http-factory-guzzle' => '1.0.0@34861658efb9899a6618cef03de46e2a52c80fc0',
  'intervention/image' => '2.5.1@abbf18d5ab8367f96b3205ca3c89fb2fa598c69e',
  'james-heinrich/getid3' => 'v1.9.20@3c15e353b9bb1252201c73394bb8390b573a751d',
  'jaybizzle/crawler-detect' => 'v1.2.96@5a53c78644c54a628c3f5ead915c35b489c92239',
  'jean85/pretty-package-versions' => '1.5.0@e9f4324e88b8664be386d90cf60fbc202e1f7fc9',
  'jenssegers/agent' => 'v2.6.4@daa11c43729510b3700bc34d414664966b03bffe',
  'laravel/framework' => 'v7.20.0@682ea946bc136aa686d5a64940ab3d4a24d5a613',
  'laravel/scout' => 'v8.1.0@415177bd2e52a0dbf87ef9143fed16e9c9b93224',
  'laravel/slack-notification-channel' => 'v2.1.0@d0a7f53342a5daa74e43e1b08dc8a7e83db152d8',
  'laravel/socialite' => 'v4.4.1@80951df0d93435b773aa00efe1fad6d5015fac75',
  'laravel/tinker' => 'v2.4.1@3c9ef136ca59366bc1b50b7f2500a946d5149c62',
  'laravel/ui' => 'v2.1.0@da9350533d0da60d5dc42fb7de9c561c72129bba',
  'league/color-extractor' => '0.3.2@837086ec60f50c84c611c613963e4ad2e2aec806',
  'league/commonmark' => '1.5.1@6d74caf6abeed5fd85d6ec20da23d7269cd0b46f',
  'league/flysystem' => '1.0.69@7106f78428a344bc4f643c233a94e48795f10967',
  'league/flysystem-aws-s3-v3' => '1.0.25@d409b97a50bf85fbde30cbc9fc10237475e696ea',
  'league/flysystem-rackspace' => '1.0.5@ba877e837f5dce60e78a0555de37eb9bfc7dd6b9',
  'league/oauth1-client' => '1.7.0@fca5f160650cb74d23fc11aa570dd61f86dcf647',
  'league/omnipay' => 'v3.0.2@9e10d91cbf84744207e13d4483e79de39b133368',
  'maennchen/zipstream-php' => '2.1.0@c4c5803cc1f93df3d2448478ef79394a5981cc58',
  'mhetreramesh/flysystem-backblaze' => '1.5.2@42549be7b3e6f372c824896ccd1d901052cb6d8c',
  'mikemccabe/json-patch-php' => '0.1.0@b3af30a6aec7f6467c773cd49b2d974a70f7c0d4',
  'mikey179/vfsstream' => 'v1.6.8@231c73783ebb7dd9ec77916c10037eff5a2b6efe',
  'mobiledetect/mobiledetectlib' => '2.8.34@6f8113f57a508494ca36acbcfa2dc2d923c7ed5b',
  'moneyphp/money' => 'v3.3.1@122664c2621a95180a13c1ac81fea1d2ef20781e',
  'monolog/monolog' => '2.1.0@38914429aac460e8e4616c8cb486ecb40ec90bb1',
  'mtdowling/jmespath.php' => '2.5.0@52168cb9472de06979613d365c7f1ab8798be895',
  'myclabs/php-enum' => '1.7.6@5f36467c7a87e20fbdc51e524fd8f9d1de80187c',
  'nesbot/carbon' => '2.36.1@ee7378a36cc62952100e718bcc58be4c7210e55f',
  'nikic/php-parser' => 'v4.6.0@c346bbfafe2ff60680258b631afb730d186ed864',
  'omnipay/common' => 'v3.0.4@d6a1bed63cae270da32b2171fe31f820d334d452',
  'omnipay/paypal' => 'v3.0.2@519db61b32ff0c1e56cbec94762b970ee9674f65',
  'omnipay/stripe' => 'v3.1.0@37df2a791e8feab45543125f4c5f22d5d305096d',
  'opis/closure' => '3.5.5@dec9fc5ecfca93f45cd6121f8e6f14457dff372c',
  'paragonie/random_compat' => 'v9.99.99@84b4dfb120c6f9b4ff7b3685f9b8f1aa365a0c95',
  'paragonie/sodium_compat' => 'v1.13.0@bbade402cbe84c69b718120911506a3aa2bae653',
  'pda/pheanstalk' => 'v4.0.1@41212671020de91086ace9e6181e69829466f087',
  'php-http/client-common' => '2.2.1@d70de2f7bf575ef19350b8aab504857943ab2922',
  'php-http/discovery' => '1.9.1@64a18cc891957e05d91910b3c717d6bd11fbede9',
  'php-http/guzzle6-adapter' => 'v2.0.1@6074a4b1f4d5c21061b70bab3b8ad484282fe31f',
  'php-http/httplug' => '2.2.0@191a0a1b41ed026b717421931f8d3bd2514ffbf9',
  'php-http/message' => '1.8.0@ce8f43ac1e294b54aabf5808515c3554a19c1e1c',
  'php-http/message-factory' => 'v1.0.2@a478cb11f66a6ac48d8954216cfed9aa06a501a1',
  'php-http/promise' => '1.1.0@4c4c1f9b7289a2ec57cde7f1e9762a5789506f88',
  'phpoption/phpoption' => '1.7.4@b2ada2ad5d8a32b89088b8adc31ecd2e3a13baf3',
  'phpseclib/phpseclib' => '2.0.28@d1ca58cf33cb21046d702ae3a7b14fdacd9f3260',
  'predis/predis' => 'v1.1.1@f0210e38881631afeafb56ab43405a92cafd9fd1',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.0.0@b7ce3b176482dbbc1245ebf52b181af44c2cf55f',
  'psr/event-dispatcher' => '1.0.0@dbefd12671e8a14ec7f180cab83036ed26714bb0',
  'psr/http-client' => '1.0.1@2dfb5f6c5eff0e91e20e913f8c5452ed95b86621',
  'psr/http-factory' => '1.0.1@12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/log' => '1.1.3@0f73288fd15629204f9d42b7055f72dacbe811fc',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'psy/psysh' => 'v0.10.4@a8aec1b2981ab66882a01cce36a49b6317dc3560',
  'pusher/pusher-php-server' => 'v4.1.4@e75e5715e3b651ec20dee5844095aadefab81acb',
  'rackspace/php-opencloud' => 'v1.16.0@d6b71feed7f9e7a4b52e0240a79f06473ba69c8c',
  'ralouphie/getallheaders' => '3.0.3@120b605dfeb996808c31b6477290a714d356e822',
  'ramsey/collection' => '1.0.1@925ad8cf55ba7a3fc92e332c58fd0478ace3e1ca',
  'ramsey/uuid' => '4.0.1@ba8fff1d3abb8bb4d35a135ed22a31c6ef3ede3d',
  'sentry/sdk' => '2.1.0@18921af9c2777517ef9fb480845c22a98554d6af',
  'sentry/sentry' => '2.4.1@407573e22e6cc46b72cff07c117eeb16bf3a17de',
  'sentry/sentry-laravel' => '1.8.0@912731d2b704fb6a97cef89c7a8b5c367cbf6088',
  'spatie/dropbox-api' => '1.15.0@0cac9d3b613514cba2fef7b8f00b41a7b9d2b2a3',
  'spatie/flysystem-dropbox' => '1.2.2@512e8d59b3f9b8a6710f932c421032cb490e9869',
  'spatie/laravel-analytics' => '3.10.0@88f20f0a82dfb2263aca082df41011ce46128d69',
  'swiftmailer/swiftmailer' => 'v6.2.3@149cfdf118b169f7840bbe3ef0d4bc795d1780c9',
  'symfony/cache' => 'v5.1.2@787eb05e137ad74fa5e51857b9884719760c7b2f',
  'symfony/cache-contracts' => 'v2.1.3@9771a09d2e6b84ecb8c9f0a7dbc72ee92aeba009',
  'symfony/console' => 'v5.1.2@34ac555a3627e324b660e318daa07572e1140123',
  'symfony/css-selector' => 'v5.1.2@e544e24472d4c97b2d11ade7caacd446727c6bf9',
  'symfony/deprecation-contracts' => 'v2.1.3@5e20b83385a77593259c9f8beb2c43cd03b2ac14',
  'symfony/dom-crawler' => 'v5.1.2@907187782c465a564f9030a0c6ace59e8821106f',
  'symfony/error-handler' => 'v5.1.2@7d0b927b9d3dc41d7d46cda38cbfcd20cdcbb896',
  'symfony/event-dispatcher' => 'v5.1.2@cc0d059e2e997e79ca34125a52f3e33de4424ac7',
  'symfony/event-dispatcher-contracts' => 'v2.1.3@f6f613d74cfc5a623fc36294d3451eb7fa5a042b',
  'symfony/finder' => 'v5.1.2@4298870062bfc667cb78d2b379be4bf5dec5f187',
  'symfony/http-foundation' => 'v5.1.2@f93055171b847915225bd5b0a5792888419d8d75',
  'symfony/http-kernel' => 'v5.1.2@a18c27ace1ef344ffcb129a5b089bad7643b387a',
  'symfony/mime' => 'v5.1.2@c0c418f05e727606e85b482a8591519c4712cf45',
  'symfony/options-resolver' => 'v5.1.2@663f5dd5e14057d1954fe721f9709d35837f2447',
  'symfony/polyfill-ctype' => 'v1.18.0@1c302646f6efc070cd46856e600e5e0684d6b454',
  'symfony/polyfill-iconv' => 'v1.18.0@6c2f78eb8f5ab8eaea98f6d414a5915f2e0fce36',
  'symfony/polyfill-intl-grapheme' => 'v1.18.0@b740103edbdcc39602239ee8860f0f45a8eb9aa5',
  'symfony/polyfill-intl-idn' => 'v1.18.0@bc6549d068d0160e0f10f7a5a23c7d1406b95ebe',
  'symfony/polyfill-intl-normalizer' => 'v1.18.0@37078a8dd4a2a1e9ab0231af7c6cb671b2ed5a7e',
  'symfony/polyfill-mbstring' => 'v1.18.0@a6977d63bf9a0ad4c65cd352709e230876f9904a',
  'symfony/polyfill-php70' => 'v1.18.0@0dd93f2c578bdc9c72697eaa5f1dd25644e618d3',
  'symfony/polyfill-php72' => 'v1.18.0@639447d008615574653fb3bc60d1986d7172eaae',
  'symfony/polyfill-php73' => 'v1.18.0@fffa1a52a023e782cdcc221d781fe1ec8f87fcca',
  'symfony/polyfill-php80' => 'v1.18.0@d87d5766cbf48d72388a9f6b85f280c8ad51f981',
  'symfony/polyfill-uuid' => 'v1.18.0@da48e2cccd323e48c16c26481bf5800f6ab1c49d',
  'symfony/process' => 'v5.1.2@7f6378c1fa2147eeb1b4c385856ce9de0d46ebd1',
  'symfony/routing' => 'v5.1.2@bbd0ba121d623f66d165a55a108008968911f3eb',
  'symfony/service-contracts' => 'v2.1.3@58c7475e5457c5492c26cc740cc0ad7464be9442',
  'symfony/string' => 'v5.1.2@ac70459db781108db7c6d8981dd31ce0e29e3298',
  'symfony/translation' => 'v5.1.2@d387f07d4c15f9c09439cf3f13ddbe0b2c5e8be2',
  'symfony/translation-contracts' => 'v2.1.3@616a9773c853097607cf9dd6577d5b143ffdcd63',
  'symfony/var-dumper' => 'v5.1.2@46a942903059b0b05e601f00eb64179e05578c0f',
  'symfony/var-exporter' => 'v5.1.2@eabaabfe1485ca955c5b53307eade15ccda57a15',
  'teamtnt/laravel-scout-tntsearch-driver' => 'v8.3.0@a13b7cfe78a70feaf061071a4b681d449b59d875',
  'teamtnt/tntsearch' => 'v2.3.0@01bb54c35a0c47eb41b145f76c384ef83b5a5852',
  'tijsverkoyen/css-to-inline-styles' => '2.2.3@b43b05cf43c1b6d849478965062b6ef73e223bb5',
  'torann/geoip' => '1.2.1@15c7cb3d2edcfbfd7e8cd6f435defc2352df40d2',
  'vlucas/phpdotenv' => 'v4.1.8@572af79d913627a9d70374d27a6f5d689a35de32',
  'voku/portable-ascii' => '1.5.2@618631dc601d8eb6ea0a9fbf654ec82f066c4e97',
  'willdurand/email-reply-parser' => '2.9.0@642bec19af70c2bf2f2611301349107fe2e6dd08',
  'zbateson/mail-mime-parser' => '1.2.2@638a5deeafe6fb78c05d509ac92ddb23a7480cfa',
  'zbateson/mb-wrapper' => '1.0.0@723f25a1ab0e4e662efa8d89f38da751c799134a',
  'zbateson/stream-decorators' => '1.0.3@a1873c22d2d6189931dd145d766157c6f75cc8d7',
  'barryvdh/laravel-ide-helper' => 'v2.7.0@5f677edc14bdcfdcac36633e6eea71b2728a4dbc',
  'barryvdh/reflection-docblock' => 'v2.0.6@6b69015d83d3daf9004a71a89f26e27d27ef6a16',
  'composer/ca-bundle' => '1.2.7@95c63ab2117a72f48f5a55da9740a3273d45b7fd',
  'composer/composer' => '1.10.9@83c3250093d5491600a822e176b107a945baf95a',
  'composer/semver' => '1.5.1@c6bea70230ef4dd483e6bbcab6005f682ed3a8de',
  'composer/spdx-licenses' => '1.5.4@6946f785871e2314c60b4524851f3702ea4f2223',
  'composer/xdebug-handler' => '1.4.2@fa2aaf99e2087f013a14f7432c1cd2dd7d8f1f51',
  'facade/flare-client-php' => '1.3.4@0eeb0de4fc1078433f0915010bd8f41e998adcb4',
  'facade/ignition' => '2.3.3@cc7df15806aad8a9915148ea4daf7f0dd0be45b5',
  'facade/ignition-contracts' => '1.0.1@aeab1ce8b68b188a43e81758e750151ad7da796b',
  'filp/whoops' => '2.7.3@5d5fe9bb3d656b514d455645b3addc5f7ba7714d',
  'fzaninotto/faker' => 'v1.9.1@fc10d778e4b84d5bd315dad194661e091d307c6f',
  'itsgoingd/clockwork' => 'v4.1.5@cd9fcb65e70954f65d50c98a5e8d5782240cbe4e',
  'justinrainbow/json-schema' => '5.2.10@2ba9c8c862ecd5510ed16c6340aa9f6eadb4f31b',
  'nunomaduro/collision' => 'v4.2.0@d50490417eded97be300a92cd7df7badc37a9018',
  'scrivo/highlight.php' => 'v9.18.1.1@52fc21c99fd888e33aed4879e55a3646f8d40558',
  'seld/jsonlint' => '1.8.0@ff2aa5420bfbc296cf6a0bc785fa5b35736de7c1',
  'seld/phar-utils' => '1.1.1@8674b1d84ffb47cc59a101f5d5a3b61e87d23796',
  'symfony/filesystem' => 'v5.1.2@6e4320f06d5f2cce0d96530162491f4465179157',
  'laravel/laravel' => 'dev-master@9068a170177b30b89211b50fd59400ab5d6d8a0a',
);

    private function __construct()
    {
        class_exists(InstalledVersions::class);
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     *
     * @psalm-param key-of<self::VERSIONS> $packageName
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function getVersion(string $packageName): string
    {
        if (class_exists(InstalledVersions::class, false)) {
            return InstalledVersions::getPrettyVersion($packageName)
                . '@' . InstalledVersions::getReference($packageName);
        }

        if (isset(self::VERSIONS[$packageName])) {
            return self::VERSIONS[$packageName];
        }

        throw new OutOfBoundsException(
            'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
        );
    }
}
