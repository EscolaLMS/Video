## Video processing

tl;dr coverts TopicVideo into HLS stream

[![swagger](https://img.shields.io/badge/documentation-swagger-green)](https://escolalms.github.io/Video/)
[![codecov](https://codecov.io/gh/EscolaLMS/Video/branch/main/graph/badge.svg?token=O91FHNKI6R)](https://codecov.io/gh/EscolaLMS/Video)
[![phpunit](https://github.com/EscolaLMS/Video/actions/workflows/test.yml/badge.svg)](https://github.com/EscolaLMS/Video/actions/workflows/test.yml)
[![downloads](https://img.shields.io/packagist/dt/escolalms/video)](https://packagist.org/packages/escolalms/video)
[![downloads](https://img.shields.io/packagist/v/escolalms/video)](https://packagist.org/packages/escolalms/video)
[![downloads](https://img.shields.io/packagist/l/escolalms/video)](https://packagist.org/packages/escolalms/video)

- `ffmpeg` must be installed
- once `EscolaLms\TopicType\TopicTypeChanged` is dispatched job [ProccessVideo](src/Jobs/ProccessVideo.php) added to queue
