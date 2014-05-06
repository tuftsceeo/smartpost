
    ______ ______                                  __            _  __     __
   / ____// ____/____ ___   ____   ___   ____ _   / /_   __  __ (_)/ /____/ /
  / /_   / /_   / __ `__ \ / __ \ / _ \ / __ `/  / __ \ / / / // // // __  / 
 / __/  / __/  / / / / / // /_/ //  __// /_/ /  / /_/ // /_/ // // // /_/ /  
/_/    /_/    /_/ /_/ /_// .___/ \___/ \__, /  /_.___/ \__,_//_//_/ \__,_/   
                        /_/           /____/                                 


                build: ffmpeg-git-20140505-64bit-static.tar.bz2
              version: N-42612-g6bd1741

 
                  gcc: 4.8.2
                 yasm: 1.2.0

               libvpx: 1.3.0-2582-g140262d
              libx264: 0.142.56 ac76440
              libx265: 1.0+4-d72770a77ff8
              libxvid: 1.3.3-1
              libwebp: 0.4.0
            libtheora: 1.1.1
          libfreetype: 2.5.2-1
          libopenjpeg: 1.5.1 

              libopus: 1.1-1
             libspeex: 1.2
            libvorbis: 1.3.2-1.3
           libmp3lame: 3.99.5
         libvo-aacenc: 0.1.3-1
       libvo-amrwbenc: 0.1.3-1
    libopencore-amrnb: 0.1.3-2
    libopencore-amrwb: 0.1.3-2

Note: ffmpeg now uses libx264's internal presets with the -preset flag.
      Look at `ffmpeg -h encoder=libx264 | less` for a complete list of libx264 options.


      This build should be stable but if you do run into problems *DO NOT* file a bug report against           
      it! You should first check out the source from git://source.ffmpeg.org/ffmpeg.git, build it and           
      see if the problem persists. If so, then and only then should you file a bug report using the
      version you compiled.

      The source code for FFmpeg and all libs are available upon request.

      Donate a few bucks via paypal if you've found this build helpful. 
      Donation link: http://goo.gl/1Ol8N


      email: john.vansickle@gmail.com
      irc: irc://irc.freenode.net #ffmpeg #libav nickname: relaxed
      url: http://johnvansickle.com/ffmpeg/
