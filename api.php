<?php
class Spriter {
	public $images;

	public function layout($files) {
		$hmargin  = 1;
		$vmargin  = 1;

		foreach ($files['name'] as $index => $name) {
			$images[$index]['name']     = $name;
			$images[$index]['type']     = $files['type'][$index];
			$images[$index]['tmp_name'] = $files['tmp_name'][$index];
			$images[$index]['size']     = $files['size'][$index];

			$wh = getimagesize($images[$index]['tmp_name']);
			$images[$index]['width']  = $wh[0];
			$images[$index]['height'] = $wh[1];
			$images[$index]['w']      = $wh[0] + $hmargin;
			$images[$index]['h']      = $wh[1] + $vmargin;
		}

		$compare = function($first, $second) {
			return $first > $second ? 1 : ($first < $second ? -1 : 0);
		};

		usort($images, function ($a, $b) use ($compare) {
			$sizeA = array($a['w'], $a['h']);
			$sizeB = array($b['w'], $b['h']);

			$diff = $compare(max($sizeB), max($sizeA));
			$diff or $diff = $compare(min($sizeB), min($sizeA));
			$diff or $diff = $compare($sizeB[1], $sizeA[1]);
			$diff or $diff = $compare($sizeB[0], $sizeA[0]);

			return $diff;
		});

		$root = array(
			'x' => 0,
			'y' => 0,
			'w' => $images[0]['w'],
			'h' => $images[0]['h']
		);

		foreach ($images as $index => $image) {
			$node = &$this->find_node($root, $image['w'], $image['h']);

			if (empty($node)) {
				$root = $this->grow($root, $image['w'], $image['h']);
				$node = &$this->find_node($root, $image['w'], $image['h']);
			}

			$images[$index] = $this->place_image($image, $node, $hmargin, $vmargin);
			$this->split_node($node, $image['w'], $image['h']);
		}

		$this->images = $images;

		return array(
			'width'  => $root['w'],
			'height' => $root['h']
		);
	}

	public function place_image($image, $node, $hmargin, $vmargin) {
		$image['cssx'] = $node['x'];
		$image['cssy'] = $node['y'];
		$image['cssw'] = $image['width'];
		$image['cssh'] = $image['height'];
		$image['x']    = $image['cssx'];
		$image['y']    = $image['cssy'];

		return $image;
	}

	public function &find_node(&$root, $w, $h) {
		$result = NULL;
		if (! empty($root['used'])) {
			$result = &$this->find_node($root['right'], $w, $h);
			if (empty($result)) {
				$result = &$this->find_node($root['down'], $w, $h);
			}
			return $result;
		} elseif ($w <= $root['w'] && $h <= $root['h']) {
			return $root;
		}
		return $result;
	}

	public function split_node(&$node, $w, $h) {
		$node['used'] = true;
		$node['down'] = array(
			'x' => $node['x'],
			'y' => $node['y'] + $h,
			'w' => $node['w'],
			'h' => $node['h'] - $h
		);
		$node['right'] = array(
			'x' => $node['x'] + $w,
			'y' => $node['y'],
			'w' => $node['w'] - $w,
			'h' => $h
		);
	}

	public function grow($root, $w, $h) {
		$canGrowDown  = ($w <= $root['w']);
		$canGrowRight = ($h <= $root['h']);

		$shouldGrowRight = $canGrowRight && ($root['h'] >= ($root['w'] + $w));
		$shouldGrowDown  = $canGrowDown  && ($root['w'] >= ($root['h'] + $h));

		if ($shouldGrowRight) {
			return $this->grow_right($root, $w, $h);
		} elseif ($shouldGrowDown) {
			return $this->grow_down($root, $w, $h);
		} elseif ($canGrowRight) {
			return $this->grow_right($root, $w, $h);
		} elseif ($canGrowDown) {
			return $this->grow_down($root, $w, $h);
		} else {
			//TODO replace this
			die('can\'t fit' . $w . 'x' . $h . 'block into root' . $root['w'] . 'x' . $root['h'] . ' - this should not happen if images are pre-sorted correctly');
		}
	}

	public function grow_right($root, $w, $h) {
		return array(
			'used'  => true,
			'x'     => 0,
			'y'     => 0,
			'w'     => $root['w'] + $w,
			'h'     => $root['h'],
			'down'  => $root,

			'right' => array(
				'x' => $root['w'],
				'y' => 0,
				'w' => $w,
				'h' => $root['h']
			)
		);
	}

	public function grow_down($root, $w, $h) {
		return array(
			'used'  => true,
			'x'     => 0,
			'y'     => 0,
			'w'     => $root['w'],
			'h'     => $root['h'] + $h,

			'down'  => array(
				'x' => 0,
				'y' => $root['h'],
				'w' => $root['w'],
				'h' => $h
			),

			'right' => $root
		);
	}

	public function create_sprite($width, $height) {
		$img = imagecreatetruecolor($width -1, $height - 1);
		$background = imagecolorallocatealpha($img, 255, 255, 255, 127);
		imagefill($img, 0, 0, $background);
		imagealphablending($img, false);
		imagesavealpha($img, true);

		$msg = '';

		foreach ($this->images as $index => $image) {
			switch (pathinfo($image['name'], PATHINFO_EXTENSION)) {
			case 'jpg':
			case 'jpeg':
				$subimg = @imagecreatefromjpeg($image['tmp_name']);
				break;
			case 'png':
				$subimg = @imagecreatefrompng($image['tmp_name']);
				break;
			case 'gif':
				$subimg = @imagecreatefromgif($image['tmp_name']);
				break;
			};
			if (!$subimg) {
				$msg .= 'Error loading ' . $image['name'] . "\n";
			} else if (empty($msg)) {
				imagecopy($img, $subimg, $image['x'], $image['y'], 0, 0, $image['width'], $image['height']);
			}
		}
		if (!empty($error)) {
			return array(
				'error' => true,
				'msg' => $msg
			);
		}
		return array(
			'error' => false,
			'img' => $img
		);
	}
}

if (! empty($_FILES['files']['size'][0])) {
	$spriter = new Spriter;
	$wh = $spriter->layout($_FILES['files']);
	$sprite = $spriter->create_sprite($wh['width'], $wh['height']);

	if ($sprite['error']) {
		exit(json_encode(array(
			'error' => true,
			'msg' => $sprite['msg']
		)));
	}

	$sprite = $sprite['img'];

	do {
		$foldername = '';
		$length     = 20;
		$possible   = '123467890abcdfghjkmnpqrtvwxyzABCDFGHJKLMNPQRTVWXYZ';

		for ($i=0; $i < $length; $i++) {
			$foldername .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
		}
	} while (is_dir('uploads/' . $foldername));

	mkdir(dirname(__FILE__) . '/uploads/' . $foldername);
	imagepng($sprite, dirname(__FILE__) . '/uploads/' . $foldername . '/sprite.png');

	$info = $wh;
	$info['url'] = 'uploads/' . $foldername . '/sprite.png';

	$oldSize = 0;

	$prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';
	$isCss = ($_POST['type'] == 'css') ? true : false;
	$isLessM = $_POST['type'] == 'lessm' ? true : false;
	$isStylus = $_POST['type'] == 'stylus' ? true : false;
	$isStylusM = $_POST['type'] == 'stylusm' ? true : false;
	$isGroup = isset($_POST['group']) ? true : false;

	if ($isCss) {
		$cssTop = '.sprite';
		$css = '';

		foreach ($spriter->images as $index => $image) {
			$selector = '.' . $prefix . pathinfo($image['name'], PATHINFO_FILENAME);
			if ($isGroup) {
				$cssTop .= ', ' . $selector;
			}
			$oldSize += $image['size'];
			$css .= $selector .' {' . "\n\t" . 'background-position: ' . ($image['cssx'] ? (-$image['cssx'] . 'px ') : '0 ') . ($image['cssy'] ? (-$image['cssy'] . 'px') : '0') . ';' . "\n}\n";
		}

		$cssTop .= " {\n\tbackground: url(\"sprite.png\") no-repeat;\n}\n";

		$css = $cssTop . $css;
	}

	if ($isLessM) {
		$cssTop = '.sprite ()' . " {\n\tbackground:url(\"sprite.png\") no-repeat;\n}\n";
		$css = '';

		foreach ($spriter->images as $index => $image) {
			$selector = '.' . $prefix . pathinfo($image['name'], PATHINFO_FILENAME);
			$oldSize += $image['size'];
			$css .= $selector .' (@x:0, @y:0) {' . "\n\t" . 'background-position: (' . ($image['cssx'] ? (-$image['cssx'] . 'px') : '0') . ' + @x) (' . ($image['cssy'] ? (-$image['cssy'] . 'px') : '0') . ' + @y);' . "\n}\n";
		}

		$css = $cssTop . $css;
	}

	if ($isStylus) {
		$cssTop = '.sprite';
		$css = '';

		foreach ($spriter->images as $index => $image) {
			$selector = '.' . $prefix . pathinfo($image['name'], PATHINFO_FILENAME);
			$oldSize += $image['size'];
			$css .= $selector . "\n\t" . ($isGroup ? "@extend .sprite\n\t" : '') . 'background-position ' . ($image['cssx'] ? (-$image['cssx'] . 'px ') : '0 ') . ($image['cssy'] ? (-$image['cssy'] . 'px') : '0') . "\n\n";
		}

		$cssTop .= " \n\tbackground url(\"sprite.png\") no-repeat\n\n";

		$css = $cssTop . $css;
	}

	if ($isStylusM) {
		$cssTop = 'sprite ()' . "\n\tbackground url(\"sprite.png\") no-repeat\n\n";
		$css = '';

		foreach ($spriter->images as $index => $image) {
			$selector = $prefix . pathinfo($image['name'], PATHINFO_FILENAME);
			$oldSize += $image['size'];
			$css .= $selector .' (x = 0, y = 0)' . "\n\t" . 'background-position (' . ($image['cssx'] ? (-$image['cssx'] . 'px') : '0') . ' + x) (' . ($image['cssy'] ? (-$image['cssy'] . 'px') : '0') . ' + y)' . "\n\n";
		}

		$css = $cssTop . $css;
	}

	$info['oldSize'] = $oldSize;
	$info['newSize'] = filesize(dirname(__FILE__) . '/uploads/' . $foldername . '/sprite.png');
	$info['css']     = $css;

	imagedestroy($sprite);

	echo json_encode($info);
} else {
	echo json_encode(array(
		'error' => true,
		'msg' => 'No files passed'
	));
}