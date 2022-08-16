<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        Log::info(' ---------------- Kernel schedule ------------------- ');
        // THIS TIME IS IN UTC //

        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@saveContentDetails')->everyFiveMinutes();
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendReminderMailAndNotification')->everyFiveMinutes();
        // $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendReminderMail')->everyFiveMinutes();
        // $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendReminderWebNotifications')->everyFiveMinutes();
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendInactivityReminderMail')->monthlyOn(5, '8:30'); // 16:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendVerificationPendingMail')->monthlyOn(5, '9:30'); // 17:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendBirthdayReminderMail')->dailyAt('2:30'); // 8:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendQuotaExhaustWarningMail')->dailyAt('1:30'); // 7:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendAnalyticsMail')->mondays()->at('1:00'); // 6:30 IST
        // $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendAnalyticsMail')->everyFiveMinutes();
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@removeTempDecryptedAttachments')->everyFiveMinutes();
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@setOrganizationEmployeeInactivityByDefinedPeriod')->dailyAt('11:30'); // 17:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@checkAndDeleteAppuserRemovedContent')->dailyAt('12:30'); // 18:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendEnterpriseRelevantWarningMails')->mondays()->at('12:30'); // 18:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendEnterprisePremiumSubscriptionExpiryDueMail')->dailyAt('12:30'); // 18:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@checkAndUpdateEnterprisePremiumSubscriptionValidity')->dailyAt('13:30'); // 19:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@removeDeactivatedAppUserAccounts')->dailyAt('15:30'); // 21:00 IST

        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@sendAppuserSracContactPendingHiMails')->dailyAt('14:30'); // 20:00 IST
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@checkAndRefreshAppuserCloudStorageTokens')->everyFiveMinutes();
        $schedule->call('\App\Http\Controllers\Api\EmailHandlerController@checkAndResyncAppuserCloudCalendarAutoSyncChanges')->cron('*/2 * * * * *'); // everyTwoMinutes // ->everyFiveMinutes(); //

        // $schedule->command('backup:run')->twiceDaily(2,14);
    }
}

