<?php

#############################################################################
# imdbGraphQLPHP                                 ed (github user: duck7000) #
# written by ed (duck7000)                                                  #
# ------------------------------------------------------------------------- #
# This program is free software; you can redistribute and/or modify it      #
# under the terms of the GNU General Public License (see doc/LICENSE)       #
#############################################################################

namespace Imdb;

/**
 * Image processing functions to calculate image url parameters to get thumbnails just like imdb uses
 * @author Ed
 */
class Image
{

    /**
     */
    public function __construct()
    {
    }

    /**
     * Calculate The total result parameter and determine if SX or SY is used
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return string
     * @note example: 'QL100_SX190_CR0,15,190,281_.jpg'
     * @note QL100 = Quality Level, 100 the highest, 0 the lowest quality
     * @note SX190 = S (scale) X190 desired width
     * @note CR = Crop (crop left and right, crop top and bottom, New width, New Height)
     * @see IMDB page / (TitlePage)
     */
    public function resultParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        // original source aspect ratio
        $ratio_orig = $fullImageWidth / $fullImageHeight;

        // new aspect ratio
        $ratio_new = $newImageWidth / $newImageHeight;

        // check if the image must be treated as SX or SY
        if ($ratio_new < $ratio_orig) {
            $cropParameter = $this->thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_UY' . $newImageHeight . '_CR' . $cropParameter . ',0,' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        } else {
            $cropParameter = $this->thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight);
            return 'QL75_UX' . $newImageWidth . '_CR0,' . $cropParameter . ',' . $newImageWidth . ',' . $newImageHeight . '_.jpg';
        }
    }

    /**
     * Calculate if cropValue has to be round to previous or next even integer
     * @param float $totalPixelCropSize how much pixels in total need to be cropped
     * @return int
     */
    private function roundInteger($totalPixelCropSize)
    {
        if ((($totalPixelCropSize - floor($totalPixelCropSize)) < 0.5)) {
            // Previous even integer
            $num = 2 * round($totalPixelCropSize / 2.0);
        } else {
            // Next even integer
            $num = ceil($totalPixelCropSize);
            $num += $num % 2;
        }
        return $num;
    }

    /**
     * Calculate HORIZONTAL (left and right) crop value for primary, cast, episode, recommendations and mainphoto images
     * @note Output is for portrait images!
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return int
     * @see IMDB page / (TitlePage)
     */
    public function thumbUrlCropParameter($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $scaledWidth = $fullImageWidth / $newScalefactor;
        $totalPixelCropSize = $scaledWidth - $newImageWidth;
        $cropValue = max($this->roundInteger($totalPixelCropSize) / 2, 0);
        return $cropValue;
    }

    /**
     * Calculate VERTICAL (Top and bottom)crop value for primary, cast, episode and recommendations images
     * @note Output is for landscape images!
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageWidth the width in pixels of the desired cropt/resized thumb image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return int
     * @see IMDB page / (TitlePage)
     */
    public function thumbUrlCropParameterVertical($fullImageWidth, $fullImageHeight, $newImageWidth, $newImageHeight)
    {
        $newScalefactor = $fullImageWidth / $newImageWidth;
        $scaledHeight = $fullImageHeight / $newScalefactor;
        $totalPixelCropSize = $scaledHeight - $newImageHeight;
        $cropValue = max($this->roundInteger($totalPixelCropSize) / 2, 0);
        return $cropValue;
    }

    /**
     * Calculate new width for mainphoto thumbnail images
     * @param int $fullImageWidth the width in pixels of the large original image
     * @param int $fullImageHeight the height in pixels of the large original image
     * @param int $newImageHeight the height in pixels of the desired cropt/resized thumb image
     * @return int
     */
    public function thumbUrlNewWidth($fullImageWidth, $fullImageHeight, $newImageHeight)
    {
        $newScalefactor = $fullImageHeight / $newImageHeight;
        $rawNewWidth = $fullImageWidth / $newScalefactor;
        return ceil($rawNewWidth);
    }

}
