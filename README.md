## Video processing 

tl;dr coverts TopicVideo into HLS stream

* `ffmpeg` must be installed 
* once `EscolaLms\Courses\Events\VideoUpdated` is dispatched job [ProccessVideo](src/Jobs/ProccessVideo.php) added to queue