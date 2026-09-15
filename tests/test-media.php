<?php
/**
 * Media Library lookups — why a file the owner can see is not on the page.
 *
 * COMMAND LINE ONLY. The theme directory is served over HTTP.
 * `php tests/test-media.php` runs it; a browser gets nothing.
 *
 * This file exists because of one report and one wrong assumption behind it.
 * The docs, the template comments and CLAUDE.md all say the same thing to the
 * owner: upload `course-{slug}-{kind}.mp4` and the video appears. The code did
 * not do that. It matched the attachment's **post_name**, which WordPress
 * derives from the title at insert and then uniquifies — so a slug already
 * held by anything, including a copy in the trash the owner cannot see, turns
 * the new upload into `course-b1-intro-2`; and a title edited afterwards in
 * رسانه never rewrites post_name at all. Either way the Media Library lists
 * the file under exactly the name the owner typed while the lookup finds
 * nothing, and the page keeps saying «به‌زودی» with nothing to point at.
 *
 * So the contract under test is the one the owner was given: the NAME on the
 * file is what publishes it, whichever of WordPress's three names ends up
 * carrying it.
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

require __DIR__ . '/wp-stub.php';

require ZANDI_THEME . '/inc/content.php';
require ZANDI_THEME . '/inc/courses.php';

$pass = 0;
$fail = 0;

function check( $label, $got, $want ) {
	global $pass, $fail;

	if ( $got === $want ) {
		++$pass;
		echo "  ok   $label\n";

		return;
	}

	++$fail;
	echo "  FAIL $label\n       got:  " . var_export( $got, true ) . "\n       want: " . var_export( $want, true ) . "\n";
}

function check_true( $label, $got ) {
	check( $label, (bool) $got, true );
}

/**
 * Registers an attachment the three ways WordPress can name one.
 *
 * @param int    $id    Attachment ID.
 * @param string $slug  post_name, or '' for none.
 * @param string $title post_title, or '' for none.
 * @param string $file  `_wp_attached_file`, or '' for none.
 * @param string $url   The URL it resolves to.
 * @return void
 */
function register_attachment( $id, $slug, $title, $file, $url ) {
	$row             = new stdClass();
	$row->ID         = $id;
	$row->post_type  = 'attachment';
	$row->post_name  = $slug;
	$row->post_title = $title;
	$row->file       = $file;

	if ( '' !== $slug ) {
		$GLOBALS['stub_posts']['attachment'][ $slug ] = $row;
	}

	$GLOBALS['stub_attachment_rows'][]   = $row;
	$GLOBALS['stub_attachments'][ $id ]  = array( 'url' => $url );
	$GLOBALS['stub_post_meta'][ $id ]['_wp_attached_file'] = $file;
}

echo "\n— Nothing uploaded —\n";
/*
 * The fresh-install case, and the one every caller has to survive: the helper
 * answers '' and the template draws its «به‌زودی» placeholder. A lookup that
 * threw, or that returned a URL to a file that is not there, would put a
 * broken player on a sales page.
 */
check( 'an unrecorded slug is empty', zandi_media( 'course-zz-intro' ), array() );
check( 'and the video helper returns an empty string', zandi_course_video( 'zz', 'intro' ), '' );

/*
 * Every slug below is used once and once only, and that is not tidiness.
 * zandi_media() memoises in a static for the life of the request — the
 * property docs/performance.md counts on — so asking for a slug before
 * registering it pins the miss for the rest of the run. A static cannot be
 * reset from outside the function, so the test works with the memo instead of
 * against it.
 */

echo "\n— The clean upload —\n";
register_attachment( 11, 'course-a1-intro', 'course-a1-intro', '2026/09/course-a1-intro.mp4', 'https://example.test/course-a1-intro.mp4' );

check( 'a matching post_name resolves', zandi_course_video( 'a1', 'intro' ), 'https://example.test/course-a1-intro.mp4' );

echo "\n— The slug WordPress renamed —\n";
/*
 * THE REPORTED BUG, 15 September 2026. The owner uploaded course-b1-intro.mp4
 * «with this label like a1 and a2» and the B1 page still said «به‌زودی».
 * WordPress had uniquified the slug to course-b1-intro-2, which is invisible
 * from رسانه — the list column shows the title. The title and the file are
 * both still exactly what was typed, and either one is enough to find it.
 */
register_attachment( 12, 'course-b1-intro-2', 'course-b1-intro', '2026/09/course-b1-intro.mp4', 'https://example.test/course-b1-intro.mp4' );

check( 'a uniquified slug is still found by its title', zandi_course_video( 'b1', 'intro' ), 'https://example.test/course-b1-intro.mp4' );

echo "\n— The title edited in wp-admin —\n";
/*
 * Renaming an attachment's title in رسانه does not rewrite post_name, so this
 * is the other half of the same failure: the library says one thing and the
 * database says another. Here neither the slug nor the title is the name the
 * owner typed — only the file on disk is.
 */
register_attachment( 13, 'img-2291', 'IMG 2291', '2026/09/course-a2-sample.mp4', 'https://example.test/course-a2-sample.mp4' );

check( 'the file on disk is the last resort and it works', zandi_course_video( 'a2', 'sample' ), 'https://example.test/course-a2-sample.mp4' );

echo "\n— The filename match is anchored —\n";
/*
 * A LIKE '%name%' here would answer `course-b1-intro` with
 * `my-course-b1-intro-old.mp4`, which is a wrong video on a sales page rather
 * than a missing one. The pattern is anchored at a path separator and at the
 * extension for exactly that reason.
 */
$GLOBALS['stub_posts']       = array();
$GLOBALS['stub_attachment_rows'] = array();
$GLOBALS['stub_attachments'] = array();

register_attachment( 21, 'x1', 'x1', '2026/09/my-course-b1-sample.mp4', 'https://example.test/wrong.mp4' );
register_attachment( 22, 'x2', 'x2', '2026/09/course-b1-sample-old.mp4', 'https://example.test/also-wrong.mp4' );

check( 'a longer filename does not answer a shorter slug', zandi_course_video( 'b1', 'sample' ), '' );

register_attachment( 23, 'x3', 'x3', '2026/09/course-a2-intro.m4v', 'https://example.test/right.m4v' );

check( 'but the real file is found whatever its extension', zandi_course_video( 'a2', 'intro' ), 'https://example.test/right.m4v' );

echo "\n— Invalidation covers every name the lookup uses —\n";
/*
 * zandi_media() caches a MISS as readily as a hit, so the flush has to clear
 * the key under every name that could have been asked for. Clearing post_name
 * alone was enough while post_name was the only lookup key; it is not any more,
 * and the symptom of getting this wrong is the original bug with a 24-hour
 * fuse on it.
 */
$post             = new stdClass();
$post->ID         = 13;
$post->post_type  = 'attachment';
$post->post_name  = 'img-2291';
$post->post_title = 'IMG 2291';

$GLOBALS['stub_post_meta'][13]['_wp_attached_file'] = '2026/09/course-a2-sample.mp4';

$names = zandi_media_names( $post );

check_true( 'the slug is cleared', in_array( 'img-2291', $names, true ) );
check_true( 'the title is cleared, sanitised the way the lookup sanitises it', in_array( 'img2291', $names, true ) );
check_true( 'and the filename is cleared', in_array( 'course-a2-sample', $names, true ) );
check( 'with no duplicates', count( $names ), count( array_unique( $names ) ) );

echo "\n— The cache namespace was bumped —\n";
/*
 * A miss cached by the old lookup would outlive this fix by up to a day and
 * look exactly like the bug it cures. The prefix retires those rows instead.
 */
$src = file_get_contents( ZANDI_THEME . '/inc/content.php' );

check_true( 'zandi_media() no longer reads the old key', false === strpos( $src, "'zandi_media_' . " ) );
check_true( 'and writes under the namespaced one', false !== strpos( $src, "'zandi_media2_' . " ) );

echo "\n";
echo $fail ? "FAILED: $fail\n" : "All $pass checks passed.\n";
exit( $fail ? 1 : 0 );
