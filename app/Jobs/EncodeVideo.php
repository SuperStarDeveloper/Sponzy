<?php

namespace App\Jobs;

use FFMpeg;
use FFMpeg\Format\Video\X264;
use App\Helper;
use App\Models\Media;
use App\Models\User;
use App\Models\Updates;
use App\Models\AdminSettings;
use App\Models\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\File;

class EncodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $video;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Media $video)
    {
       $this->video = $video;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      // Admin Settings
      $settings = AdminSettings::first();

      // Paths
      $disk = 'default';
      $path = 'temp/';
      $videoPathDisk = $path.$this->video->video;
      $videoPathDiskMp4 = $this->video->id.str_random(20).uniqid().now()->timestamp.'-converted.mp4';
      $urlWatermark = ucfirst(Helper::urlToDomain(url('/'))).'/'.$this->video->user()->username;
      $font = public_path('webfonts/arial.TTF');

      // Create Thumbnail Video
        try {
          $videoPoster = str_random(20).uniqid().now()->timestamp.'-poster.jpg';

          $ffmpeg = FFMpeg::fromDisk($disk)
          ->open($videoPathDisk)
            ->getFrameFromSeconds(1)
            ->export()
          ->toDisk($disk);

          $ffmpeg->save($path.$videoPoster);

          // Clean
          FFMpeg::cleanupTemporaryFiles();

        } catch (\Exception $e) {
          $videoPoster = null;
        }

      // Create a video format...
      $format = new X264();
      $format->setAudioCodec('aac');
      $format->setVideoCodec('libx264');
      $format->setKiloBitrate(0);

      try {
        // open the uploaded video from the right disk...
        if ($settings->watermark_on_videos == 'on') {
          $ffmpeg = FFMpeg::fromDisk($disk)
              ->open($videoPathDisk)
              ->addFilter(['-strict', -2])
              ->addFilter(function ($filters) use ($urlWatermark, $font) {
                  $filters->custom("drawtext=text=$urlWatermark:fontfile=$font:x=W-tw-15:y=H-th-15:fontsize=30:fontcolor=white");
                })
              ->export()
              ->toDisk($disk)
              ->inFormat($format);

            $ffmpeg->save($path.$videoPathDiskMp4);

        } else {
          $ffmpeg = FFMpeg::fromDisk($disk)
              ->open($videoPathDisk)
              ->addFilter(['-strict', -2])
              ->export()
              ->toDisk($disk)
              ->inFormat($format);

            $ffmpeg->save($path.$videoPathDiskMp4);
        }

        // Clean
        FFMpeg::cleanupTemporaryFiles();

        // Delete old video
       Storage::disk('default')->delete($videoPathDisk);

          // Update name video on Media table
          Media::whereId($this->video->id)->update([
              'video' => $videoPathDiskMp4,
              'encoded' => 'yes',
              'video_poster' => $videoPoster ?? null
          ]);

          // Check if there are other videos that have not been encoded
          $videos = Media::whereUpdatesId($this->video->updates_id)
              ->where('video', '<>', '')
              ->whereEncoded('no')
              ->get();

              if ($videos->count() == 0) {

                // Update date the post and status
                  Updates::whereId($this->video->updates_id)->update([
                      'date' => now(),
                      'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
                  ]);

                // Notify to user - destination, author, type, target
            		Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);
              }

              // Move Video File to Storage
              $this->moveFileStorage($videoPathDiskMp4);

              // Move Video Poster to Storage
              if ($videoPoster) {
                $this->moveFileStorage($videoPoster);
              }

      } catch (\Exception $e) {

        // Update date the post and status
        $post = Updates::whereId($this->video->updates_id)
          ->whereStatus('encode')
          ->update([
              'date' => now(),
              'status' => $settings->auto_approve_post == 'on' ? 'active' : 'pending'
          ]);

          if ($post) {
            // Notify to user - destination, author, type, target
            Notifications::send($this->video->user_id, $this->video->user_id, 9, $this->video->updates_id);

            // Move Video File to Storage
            $this->moveFileStorage($videoPathDiskMp4);

            // Move Video Poster to Storage
            if ($videoPoster) {
              $this->moveFileStorage($videoPoster);
            }
          }
      }

    }// End Handle

    /**
       * Move file to Storage
       *
       * @return void
       */
    protected function moveFileStorage($file)
    {
      $path = config('path.videos');
      $localFile = public_path('temp/'.$file);

      // Move the file...
      Storage::putFileAs($path, new File($localFile), $file);

      // Delete temp file
      unlink($localFile);

   } // end method moveFileStorage
}