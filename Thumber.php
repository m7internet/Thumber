<?php
/**
 * Class Thumber
 * @copyright https://indexed.dk
 * @author peter@indexed.dk
 * @version 0.1
 */
class Thumber
{
    private $sizes = [];
    private $noImage = '';
    private $debug = false;
    private $destination;
    private $source;
    private $enableLogs =  true;

    /**
     * @param $size
     * @return $this
     */
    public function allowSize($size)
    {
        $this->sizes[] = $size;
        return $this;
    }

    public function destination($destination)
    {
        $this->destination = trim($destination, '/');
        return $this;
    }

    public function source($source)
    {
        $this->source = trim($source, '/');
        return $this;
    }

    /**
     * @param $debug
     * @return $this
     */
    public function debug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @param $noImage
     * @return $this
     */
    public function setNoImage($noImage)
    {
        $this->noImage = $noImage;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnableLogs()
    {
        return $this->enableLogs;
    }

    /**
     * @param bool $enableLogs
     * @return Thumber
     */
    public function setEnableLogs($enableLogs)
    {
        $this->enableLogs = $enableLogs;
        return $this;
    }

    /**
     *
     */
    public function output()
    {
        $path = $_SERVER['REQUEST_URI'];
        $path = trim($path, '/');
        $path = str_replace($this->destination, '', $path);
        $path = trim($path, '/');

        $dirs = explode('/', $path);

        $action = $dirs[0];

        unset($dirs[0]);

        $srcFile = $_SERVER['DOCUMENT_ROOT'].'/'.trim($this->source, '/').'/'.implode('/', $dirs);
        $targetFile = trim($this->destination, '/').'/'.$action.'/'.implode('/', $dirs);
        $targetDir = $_SERVER['DOCUMENT_ROOT'].'/'.dirname($targetFile);

        if($this->debug) {
            echo "srcFile: $srcFile\n";
            echo "action: $action\n";
            echo "target dir: $targetDir\n";
            echo "target file: $targetFile\n";
        }

        $loadImage = $this->noImage;

        try {
            if (!file_exists($srcFile)) {
                throw new \Exception('File do not exist');
            }

            if (!in_array($action, $this->sizes)) {
                throw new \Exception('Action "'.$action.'"" not allowed ');
            }

            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $this->accessLog($srcFile, $action);

            /*
             * Google pageinsights reccommendation for optimal size
             */
            $exec = "/usr/bin/convert -format jpg $srcFile -strip -quality 80 -auto-orient -resize ".$action." -sampling-factor 4:2:0 -interlace JPEG -colorspace RGB ".$_SERVER['DOCUMENT_ROOT']."/$targetFile";

            if($this->debug) {
                echo $exec . "\n";
            }

            exec($exec);

            if(file_exists($_SERVER['DOCUMENT_ROOT']."/$targetFile")) {
                $loadImage = '/' . $targetFile;
            }

        } catch (\Exception $e) {
            if($this->debug) {
                echo 'ERROR: '.$e->getMessage();
            }

            $this->errorLog($e->getMessage());
        }

        if(!$this->debug) {
            header("Location: $loadImage");
        }
    }

    /**
     * @param $src
     * @param $action
     */
    private function accessLog($src, $action)
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/logs/thumb_access.log', 'a+');

        $srcSize = filesize($src);

        $data = '['.$_SERVER['REMOTE_ADDR'].'] ['.date('Y-m-d H:i:s').'] ['.$src.', '.$srcSize.'] ['.$action.'] ['.memory_get_peak_usage().']'."\n";

        fwrite($fp, $data);
        fclose($fp);
    }

    /**
     * @param $error
     */
    private function errorLog($error)
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/logs/thumb_error.log', 'a+');

        $data = '['.$_SERVER['REMOTE_ADDR'].'] ['.date('Y-m-d H:i:s').'] ['.$error.'] ['.$_SERVER['REQUEST_URI'].'] ['.memory_get_peak_usage().']'."\n";

        fwrite($fp, $data);
        fclose($fp);
    }
}