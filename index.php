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
    .in-options {
      list-style: none;
      padding: 0;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .in-options li {

    }
    .in-options video {
      max-height: 240px;
      cursor: pointer;
    }
    .in-up-radio {
      display: none;
    }
    .in-up-radio:checked + video {
      outline: 4px solid blue;
      outline-offset: 2px;
    }
    label {
      display: block;
    }
    textarea {
      text-transform: uppercase;
      text-transform: uppercase;
      min-width: 60ch;
      min-height: 7ch;
      resize: vertical;
    }
  </style>
</head>
<body>
  <main>
    <h1>Mattify</h1>
    <?php if( $show_file ) { ?>
      <p>
        <a href="<?php echo $show_file; ?>" download>
          <video src="<?php echo $show_file; ?>" autoplay loop></video>
        </a>
      </p>
    <?php } ?>

    <form method="post" action="./">
      <h2>Generate</h2>
      <p>
        Video:
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
        <label for="top">Top text:</label>
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
        <label for="bottom">Bottom text:</label>
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
