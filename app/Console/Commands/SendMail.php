<?php

namespace App\Console\Commands;

use App\Mail\DailyMail;
use App\Repositories\Admin\EmailSubRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMail extends Command
{

    public $Emailsubrep;

    public function __construct(EmailSubRepository $Emailsubrep){
        parent::__construct();
        
        $this->Emailsubrep = $Emailsubrep;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command sends mail to all users who have subscribed to our newsletter';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emails = $this->Emailsubrep->GetSubscribers();

        
        foreach ($emails as $email) {
            $daysSinceCreated = Carbon::parse($email->created_at)->diffInDays(Carbon::now());
            switch ($daysSinceCreated) {
                case 2:
                    Mail::to($email->email)->send(new DailyMail('Let’s Get Everything in Order!', 'email.daytwo', $email));
                    return true;
                    break;
                case 4:
                    Mail::to($email->email)->send(new DailyMail('Let’s Get Everything Ready', 'email.daythree', $email));
                    return true;
                    break;
                case 6:
                    Mail::to($email->email)->send(new DailyMail('Move Day Is Getting Closer', 'email.dayfour', $email));
                    return true;
                    break;
                case 8:
                    Mail::to($email->email)->send(new DailyMail('Last Checks Before Moving Day', 'email.dayfive', $email));
                    return true;
                    break;
                case 10:
                    Mail::to($email->email)->send(new DailyMail('Let’s Get Everything Organised', 'email.daysix', $email));
                    return true;
                    break;
                case 12;
                    Mail::to($email->email)->send(new DailyMail('Moving Day is Here – 10 Tips for a Smooth Move', 'email.dayseven', $email));
                    return true;
                    break;
                default:
                    return false;
                    break;
            }
        }

    }
}
