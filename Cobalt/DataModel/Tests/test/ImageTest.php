<?php

use Cobalt\DataModel\Tests\ImageDebugModel;
use Cobalt\DataModel\Types\ImageType;
use Cobalt\JobQueue\Controllers\JobStatus;
use Cobalt\JobQueue\Enums\JobState;
use Cobalt\JobQueue\Models\Job;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase {
    function test_image_filters() {
        $model = new ImageDebugModel();
        $filename = __ENV_ROOT__ . "/shared/img/pexels-photo-267350.webp";
        $_FILES = [
            'image' => [
                'name' => [pathinfo($filename, PATHINFO_FILENAME)],
                'type' => [mime_content_type($filename)],
                'tmp_name' => [$filename],
                'error' => [UPLOAD_ERR_OK],
                'size' => [filesize($filename)],
            ]
        ];
        $job = new Job();
        $startTime = time();

        $now = time();
        $filterResult = $model->filterDocument([
            'image' => ImageType::FILE_UPLOAD_INDICATOR
        ]);
        $this->assertTrue($filterResult->job->hasItems(), "Job must have items!");
        while($now - $startTime <= 60) {
            $now = time();
            sleep(1);
            /** @var Job $result */
            $result = $job->findOne(['_id' => $model->filterResult->job->getJobId()]);

            $state = $result->getState();
            if($state == JobState::CREATED) $this->assertTrue(false, "Job state should not be `CREATED` at this stage!");
            if($state == JobState::QUEUED) $this->assertTrue(false, "Job state should have been modified already.");
            if($state == JobState::PROCESSING) continue;
            if($state == JobState::FINISHED) $this->assertTrue(true, 'Job state is finished');
            if($state == JobState::FAILED) $this->assertTrue(false, $result->getMessage());
        }
        // Clean up after ourselves
        unset($_FILES);
        
    }

    // function test_image_upload() {

    // }
}