<?php
$infile_format = 'webm';
$outfile_format = 'mp4';
$font_sizes = [ 6, 9, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 34, 38, 46 ];
$files = array_map( function( $file ) { return basename( $file, '.webm' ); }, glob('in/*.' . $infile_format) );

// Error handler
function error( $message ){
  header('HTTP/1.1 500 Internal Server Error');
  echo '<pre>', $message, '</pre>';
  die();
}

// Generator; redirects after
function generate( $in, $top, $top_size, $bottom, $bottom_size ) {
  global $files, $infile_format, $outfile_format, $font_sizes;

  if( ! in_array( $in, array_keys( $files ) ) ) error( 'Invalid input file' );
  $infile = realpath( 'in/' . $in . '.' . $infile_format );
  $top = strtoupper( $top );
  $top_size = intval( $top_size );
  if( ! in_array( $top_size, $font_sizes ) ) error( 'Invalid top font size' );
  $bottom = strtoupper( $bottom );
  $bottom_size = intval( $bottom_size );
  if( ! in_array( $bottom_size, $font_sizes ) ) error( 'Invalid bottom font size' );
  $hash = md5( implode(':', [ $infile, $top, $top_size, $bottom, $bottom_size ] ) );

  // check if pre-generated
  $outfile = realpath('out') . '/' . $hash . '.' . $outfile_format;
  if( ! file_exists( $outfile ) ) {
    // generate output
    $outfile = realpath('out') . '/' . $hash . '.' . $outfile_format;
    $top_file_name = tempnam( 'tmp', "{$hash}-top" );
    $bottom_file_name = tempnam( 'tmp', "{$hash}-bottom" );
    file_put_contents( $top_file_name, "1\n00:00:00,000 --> 05:00:00,000\n${top}" );
    file_put_contents( $bottom_file_name, "1\n00:00:00,000 --> 05:00:00,000\n{$bottom}" );
    file_put_contents( "out/{$hash}.json", json_encode( [ $in, $top, $top_size, $bottom, $bottom_size ] ) );
    $cmd = "ffmpeg -y -i {$infile} -filter_complex \"subtitles={$bottom_file_name}:force_style='Alignment=2,Fontsize={$bottom_size}',subtitles={$top_file_name}:force_style='Alignment=6,Fontsize={$top_size}'\" -c:a copy {$outfile}";
    exec($cmd);

    // tidy up
    unlink( $top_file_name );
    unlink( $bottom_file_name );
  }

  // redirect and show
  header('Location: ./?h=' . $hash);
  die();
}

if( $_POST['in'] ) generate( $_POST['in'], $_POST['top'], $_POST['top_size'], $_POST['bottom'], $_POST['bottom_size'] );

// Which one to show?
if( ( preg_match('/^[0-9a-f]{32}$/', $_GET['h'] ) ) && ( file_exists( realpath('out') . '/' . $_GET['h'] . '.json' ) ) ) {
  list( $in, $top, $top_size, $bottom, $bottom_size ) = json_decode( file_get_contents( "out/{$_GET['h']}.json" ) );
  if( file_exists( realpath('out') . '/' . $_GET['h'] . '.' . $outfile_format ) ) {
    // if mp4 file exists already, we can just show that
    $show_file = "out/{$_GET['h']}." . $outfile_format;
  } else {
    // OTHERWISE we generate that based on the JSON and loop around again
    generate( $in, $top, $top_size, $bottom, $bottom_size );
  }
}

// Default font sizes
$top_size = $top_size ?? 16;
$bottom_size = $bottom_size ?? 16;

?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mattify</title>
  <style type="text/css">
    body {
      margin: 0;
      display: flex;
      flex-direction: column;
      font-family: sans-serif;
      color: #000;
      background: #eee;
    }
    main {
      max-width: 960px;
      align-self: center;
    }
    .output {
      text-align: center;
    }
    h1 {
      text-align: center;
    }
    h1 svg {
      height: 100px;
      max-height: 15vh;
    }
    video {
      max-height: 100vh;
    }
    .in-options {
      list-style: none;
      padding: 0;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .in-options video {
      max-height: 240px;
      cursor: pointer;
    }
    .in-up-radio {
      display: none;
    }
    .in-up-radio:checked + video {
      outline: 4px solid #000;
      outline-offset: 2px;
    }
    label {
      display: block;
    }
    input, textarea, select {
      font-family: inherit;
      font-size: inherit;
      padding: 4px;
    }
    textarea {
      text-transform: uppercase;
      text-transform: uppercase;
      min-width: 60ch;
      min-height: 7ch;
      resize: vertical;
    }
    footer {
      padding: 6px 12px;
      font-size: 80%;
      color: #eee;
      background: #555;
      text-align: center;
    }
    footer a {
      color: #fff;
    }
  </style>
</head>
<body>
  <header>
    <h1><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 54.803 19.773" aria-label="Mattify: should'a put'a hat on it"><path d="M21 17.07l-5.27 1.17a17.09 17.09 0 0 1-7.54 0L3 17.07a1.86 1.86 0 0 1-1.5-1.81 1.86 1.86 0 0 1 2.27-1.82l4.46 1a17.7 17.7 0 0 0 3.77.41 17.7 17.7 0 0 0 3.77-.41l4.46-1a1.86 1.86 0 0 1 2.27 1.82 1.86 1.86 0 0 1-1.5 1.81zM5.34 10l2.89.64A17.7 17.7 0 0 0 12 11a17.7 17.7 0 0 0 3.77-.41l2.89-.59M4.73 13.65l1.2-7.23a2.48 2.48 0 0 1 2.45-2.08 2.45 2.45 0 0 1 1.39.42L12 6.25l2.23-1.49a2.45 2.45 0 0 1 1.39-.42 2.49 2.49 0 0 1 1.61.59 2.46 2.46 0 0 1 .84 1.49l1.2 7.23" transform="matrix(.468319 -.125486 .125486 .468319 -2.223331 .331906)" fill="none" stroke="#000" stroke-miterlimit="10" stroke-width="1.5"/><path d="M4.253 5.907v.007l.064-.007zm7.24 0L8.8 11.037 6.802 7.195c-.689.212-1.55.43-2.549.588v7.14h1.753V8.955L8.33 13.4h.953l2.324-4.445v5.969h1.752V5.907zm6.311 0h1.55l3.429 9.017h-1.804l-.838-2.248h-3.15l-.825 2.248h-1.803zm2.02 5.537l-1.245-3.543-1.295 3.543zm9.804-4h-2.882v7.48h-1.74v-7.48h-2.883V5.907h7.506z" fill-opacity=".981"/><path d="M35.373 7.444H32.49v7.48h-1.74v-7.48h-2.884V5.907h7.506zm1.117 7.478V5.905h1.753v9.017zm3.632 0V5.905h6.058v1.537h-4.304v2.324h3.581v1.422h-3.58v3.734zm8.304-9.015l2.223 4.343 2.26-4.343h1.893l-3.277 5.867v3.15h-1.74v-3.175l-3.264-5.842z" fill-opacity=".981"/><g fill-opacity=".981"><path d="M4.866 18.806q-.285 0-.547-.095-.262-.095-.457-.286l.119-.162q.2.181.414.267.214.086.472.086.323 0 .519-.133.2-.139.2-.381 0-.115-.053-.191-.047-.081-.147-.133-.1-.058-.248-.1-.148-.043-.343-.09-.21-.048-.367-.096-.152-.048-.252-.114-.1-.067-.152-.162-.048-.096-.048-.243 0-.186.071-.324.072-.138.196-.229.128-.09.29-.133.167-.048.353-.048.29 0 .514.1.224.096.348.248l-.134.129q-.124-.143-.324-.215-.195-.071-.419-.071-.138 0-.262.028-.119.03-.21.091-.09.062-.142.162-.052.095-.052.229 0 .109.033.176.038.066.114.114.081.048.205.086.124.033.295.076.234.057.415.11.18.052.3.128.124.076.185.181.067.105.067.267 0 .333-.262.533-.257.195-.69.195zm3.53-.047h-.239v-1.381q0-.92-.614-.92-.152 0-.3.058-.143.057-.271.162-.124.104-.22.247-.095.138-.142.3v1.534h-.238v-3.477h.238v1.59q.152-.29.419-.461.271-.172.58-.172.206 0 .353.077.148.076.243.219.1.143.143.347.048.2.048.453zm1.757.047q-.262 0-.486-.1-.219-.104-.38-.28-.163-.177-.253-.41-.09-.233-.09-.49 0-.263.09-.496.095-.233.257-.41.162-.176.381-.276.224-.105.481-.105.257 0 .476.105.22.1.381.276.167.177.257.41.096.233.096.495 0 .258-.096.49-.09.234-.252.41-.162.177-.386.281-.219.1-.476.1zm-.972-1.271q0 .219.077.414.076.19.204.334.134.142.31.228.176.081.376.081.2 0 .376-.08.177-.087.31-.234.133-.148.21-.338.076-.196.076-.42 0-.219-.076-.409-.077-.195-.21-.338-.133-.148-.31-.234-.171-.085-.371-.085-.2 0-.376.085-.176.086-.31.234-.128.147-.21.348-.076.195-.076.414zm3.52 1.271q-.79 0-.79-1.095v-1.429h.237v1.396q0 .914.624.914.158 0 .305-.052.148-.057.276-.153.129-.1.224-.238.1-.138.157-.305v-1.562h.239v2.143q0 .12.1.12v.214q-.024.005-.043.005h-.029q-.095 0-.171-.057-.072-.062-.072-.162v-.372q-.162.3-.447.467-.281.167-.61.167zm2.048-3.524h.243v2.986q0 .138.076.22.08.08.219.08.052 0 .124-.01.076-.014.138-.038l.048.19q-.081.034-.2.053-.12.024-.205.024-.2 0-.324-.12-.12-.123-.12-.328zm2.233 3.524q-.257 0-.476-.104-.219-.11-.38-.286-.158-.181-.248-.41-.086-.233-.086-.48 0-.258.086-.491.085-.234.238-.41.152-.176.357-.28.21-.106.457-.106.162 0 .305.048.143.048.267.129.123.076.223.18.1.105.172.224v-1.538h.238v3.143q0 .12.1.12v.214q-.029.005-.048.005-.019.005-.038.005-.095 0-.162-.072-.066-.076-.066-.157v-.276q-.153.248-.41.395-.252.148-.529.148zm.043-.214q.129 0 .277-.052.147-.053.271-.143.129-.095.219-.214.09-.12.105-.258v-.814q-.053-.133-.153-.252-.095-.124-.219-.21-.123-.09-.266-.143-.143-.052-.277-.052-.214 0-.39.095-.176.09-.3.243-.124.148-.19.343-.067.19-.067.395 0 .214.076.405.076.19.21.338.138.148.314.233.18.086.39.086zm1.829-2.4v-.834h.214v.834z"/><use xlink:href="#B"/><path d="M24.712 18.806q-.32 0-.572-.166-.247-.172-.4-.415v1.548h-.238v-3.49h.22v.495q.152-.238.4-.386.252-.153.538-.153.257 0 .476.11.219.11.376.29.157.177.243.41.09.233.09.476 0 .258-.08.49-.081.234-.234.41-.148.177-.357.281-.21.1-.462.1zm-.057-.214q.214 0 .39-.09.177-.09.3-.239.124-.152.19-.342.068-.196.068-.396 0-.214-.077-.405-.076-.195-.214-.342-.133-.148-.314-.234-.181-.09-.386-.09-.129 0-.276.057-.143.052-.272.148-.128.09-.219.21-.09.118-.105.252v.814q.062.138.158.257.095.12.214.21.119.09.257.143.138.047.286.047zm2.529.214q-.79 0-.79-1.095v-1.429h.237v1.396q0 .914.624.914.157 0 .305-.052.148-.057.276-.153.129-.1.224-.238.1-.138.157-.305v-1.562h.238v2.143q0 .12.1.12v.214q-.024.005-.043.005h-.028q-.095 0-.172-.057-.071-.062-.071-.162v-.372q-.162.3-.448.467-.28.167-.61.167zm3.133-.166l-.066.038q-.043.024-.115.047-.066.024-.152.043-.086.02-.186.02-.1 0-.19-.03-.086-.028-.153-.085-.066-.057-.104-.138-.038-.081-.038-.19v-1.867h-.348v-.196h.348v-.847h.238v.847h.576v.196h-.576v1.81q0 .137.095.209.095.067.214.067.148 0 .253-.048.11-.052.133-.067zm.381-2.448v-.834h.215v.834zm1.477 2.614q-.172 0-.32-.057-.147-.062-.261-.162-.11-.104-.172-.243-.062-.142-.062-.304 0-.162.077-.296.076-.133.21-.228.137-.1.328-.153.19-.057.419-.057.2 0 .4.038.205.034.367.096v-.243q0-.353-.2-.558-.2-.21-.543-.21-.181 0-.386.077-.2.076-.41.22l-.09-.163q.476-.324.905-.324.447 0 .705.262.257.258.257.715v1.21q0 .118.105.118v.215q-.024.005-.053.005-.024.005-.043.005-.095 0-.152-.062-.057-.067-.067-.157v-.205q-.171.224-.438.343-.267.119-.576.119zm.047-.19q.277 0 .505-.105.234-.105.357-.276.077-.1.077-.19v-.439q-.172-.066-.358-.1-.185-.033-.385-.033-.186 0-.338.043-.153.038-.262.11-.11.07-.172.176-.057.1-.057.223 0 .124.048.234.047.11.133.19.086.077.2.124.12.043.252.043zm5.149.143h-.238v-1.381q0-.92-.615-.92-.152 0-.3.058-.143.057-.271.162-.124.104-.22.247-.095.138-.142.3v1.534h-.238v-3.477h.238v1.59q.152-.29.419-.461.271-.172.58-.172.206 0 .353.077.148.076.243.219.1.143.143.347.048.2.048.453z"/><use xlink:href="#B" x="18.369"/><path d="M41.733 18.64l-.067.038q-.042.024-.114.047-.067.024-.152.043-.086.02-.186.02-.1 0-.19-.03-.086-.028-.153-.085-.067-.057-.105-.138-.038-.081-.038-.19v-1.867h-.347v-.196h.347v-.847h.238v.847h.577v.196h-.577v1.81q0 .137.096.209.095.067.214.067.148 0 .252-.048.11-.052.134-.067zm2.681.166q-.262 0-.485-.1-.22-.104-.381-.28-.162-.177-.253-.41-.09-.233-.09-.49 0-.263.09-.496.095-.233.257-.41.162-.176.381-.276.224-.105.481-.105.258 0 .477.105.219.1.38.276.167.177.258.41.095.233.095.495 0 .258-.095.49-.09.234-.253.41-.161.177-.385.281-.22.1-.477.1zm-.971-1.271q0 .219.076.414.076.19.205.334.133.142.31.228.176.081.376.081.2 0 .376-.08.176-.087.31-.234.133-.148.209-.338.076-.196.076-.42 0-.219-.076-.409-.076-.195-.21-.338-.133-.148-.31-.234-.17-.085-.37-.085-.2 0-.377.085-.176.086-.31.234-.128.147-.209.348-.076.195-.076.414zm4.777 1.224h-.238v-1.381q0-.481-.139-.7-.133-.22-.428-.22-.157 0-.315.058-.152.057-.285.162-.134.104-.234.247-.1.138-.147.3v1.534h-.238v-2.477h.223v.59q.077-.142.186-.256.114-.12.253-.2.142-.086.3-.129.157-.048.323-.048.405 0 .572.286.167.281.167.81zm1.943 0v-2.477h.238v2.477zm0-3.039v-.438h.238v.438zm2.091 2.92q-.02.01-.067.038-.043.024-.114.047-.067.024-.153.043-.086.02-.186.02-.1 0-.19-.03-.086-.028-.153-.085-.066-.057-.104-.138-.038-.081-.038-.19v-1.867h-.348v-.196h.348v-.847h.238v.847h.576v.196h-.576v1.81q0 .137.095.209.095.067.214.067.148 0 .253-.048.11-.052.133-.067z"/></g><defs ><path id="B" d="M20.33 18.806q-.171 0-.319-.057-.147-.062-.262-.162-.11-.104-.171-.243-.062-.142-.062-.304 0-.162.076-.296.076-.133.21-.228.138-.1.328-.153.19-.057.42-.057.2 0 .4.038.204.034.366.096v-.243q0-.353-.2-.558-.2-.21-.543-.21-.18 0-.385.077-.2.076-.41.22l-.09-.163q.476-.324.904-.324.448 0 .705.262.257.258.257.715v1.21q0 .118.105.118v.215q-.024.005-.052.005-.024.005-.043.005-.095 0-.152-.062-.058-.067-.067-.157v-.205q-.172.224-.438.343-.267.119-.577.119zm.048-.19q.276 0 .505-.105.233-.105.357-.276.076-.1.076-.19v-.439q-.171-.066-.357-.1-.186-.033-.386-.033-.185 0-.338.043-.152.038-.262.11-.11.07-.171.176-.057.1-.057.223 0 .124.047.234.048.11.134.19.085.077.2.124.119.043.252.043z"/></defs></svg></h1>
  </header>
  <main>
    <?php if( $show_file ) { ?>
      <section class="output">
        <p>
          <a href="<?php echo $show_file; ?>" download>
            <video src="<?php echo $show_file; ?>" autoplay loop></video>
          </a>
        </p>
        <p>
          <a href="<?php echo $show_file; ?>" download>Download</a> (e.g. for WhatsApp)
        </p>
      </section>
    <?php } ?>

    <form class="generate" method="post" action="./">
      <h2>Make Your Own</h2>
      <p>
        Choose a video:
      </p>
      <ul class="in-options">
        <?php foreach( $files as $file ) { ?>
          <li>
            <label for="in-<?php echo $file; ?>">
              <input type="radio" class="in-up-radio" name="in" value="<?php echo $file; ?>" id="in-<?php echo $file; ?>"<?php if($in == $file) echo ' checked'; ?>>
              <video src="in/<?php echo $file; ?>.webm" loop></video>
            </label>
          </li>
          <option value="<?php echo $file; ?>"><?php echo $desc; ?></option>
        <?php } ?>
      </ul>
      <p>
        <label for="top">Enter some top text:</label>
        <textarea name="top" id="top"><?php echo htmlspecialchars( $top ); ?></textarea>
      </p>
      <p>
        <label for="top_size">Top font size:</label>
        <select name="top_size" id="top_size">
          <?php foreach( $font_sizes as $font_size ) { ?>
            <option<?php if($top_size == $font_size) echo ' selected'; ?>><?php echo $font_size; ?></option>
          <?php } ?>
        </select>
      </p>
      <p>
        <label for="bottom">Enter some bottom text:</label>
        <textarea name="bottom" id="bottom"><?php echo htmlspecialchars( $bottom ); ?></textarea>
      </p>
      <p>
        <label for="bottom_size">Bottom font size:</label>
        <select name="bottom_size" id="bottom_size">
          <?php foreach( $font_sizes as $font_size ) { ?>
            <option<?php if($bottom_size == $font_size) echo ' selected'; ?>><?php echo $font_size; ?></option>
          <?php } ?>
        </select>
      </p>
      <input type="submit" value="Generate">
    </form>

    <section class="explanation">
      <h2>What and Why?</h2>
      <p>
        If all started when I was backing up my WhatsApp messages and discovered that I had, for some reason, <a href="in/website-game.webm">a video</a> of
        my friend Matt repeating "this could be a web game". I can't recall the context, but it was clearly something to do with my habit of making
        stupid webby interactives. It turned out that I had a <em>lot</em> of portrait videos of Matt, so I stripped the audio from them all and made this
        silly tool. There's a couple of other people available too.
      </p>
      <p>
        Click a video, enter some text, click generate, and see the output. Click the resulting video to download it as an <tt>.mp4</tt> file suitable for
        sending via WhatsApp or what-have-you.
      </p>
    </section>
  </main>

  <footer>
    <p>
      A <a href="https://things.danq.me/">thing</a> by <a href="https://danq.me/">Dan Q</a> |
      for <a href="https://abnib.co.uk/">Abnib</a> |
      <a href="https://github.com/Dan-Q/mattify">Source</a>
    </p>
  </footer>

  <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function(){
      const inOptions = document.querySelector('.in-options');
      function selectedInVideoChanged(){
        [...inOptions.querySelectorAll('video')].forEach(v=>v.pause());
        const selectedVideo = inOptions.querySelector('.in-up-radio:checked + video');
        if(selectedVideo) {
          selectedVideo.play();
          selectedVideo.autoplay = true; // in case not finished loading yet!
        }
      }
      inOptions.addEventListener('change', selectedInVideoChanged);
      selectedInVideoChanged();
    });

    const form = document.querySelector('form');
    form.addEventListener('submit', function(){
      form.querySelectorAll('input, textarea').forEach(i=>i.readonly=true);
      form.querySelectorAll('input[type="submit"]').forEach(i=>i.disabled=true);
    });
  </script>
</body>
</html>
