<?php

/**
 * @see       https://github.com/laminas-api-tools/api-tools-content-negotiation for the canonical source repository
 * @copyright https://github.com/laminas-api-tools/api-tools-content-negotiation/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas-api-tools/api-tools-content-negotiation/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\ApiTools\ContentNegotiation\Filter;

use DirectoryIterator;
use Laminas\ApiTools\ContentNegotiation\Request;
use Laminas\Http\Request as HttpRequest;
use PHPUnit_Framework_TestCase as TestCase;

class RenameUploadTest extends TestCase
{
    public function setUp()
    {
        $this->tmpDir    = sys_get_temp_dir() . '/api-tools-content-negotiation-filter';
        $this->uploadDir = $this->tmpDir . '/upload';
        $this->targetDir = $this->tmpDir . '/target';
        $this->tearDown();
        mkdir($this->tmpDir);
        mkdir($this->uploadDir);
        mkdir($this->targetDir);
    }

    public function tearDown()
    {
        if (! is_dir($this->tmpDir)) {
            return;
        }

        if (is_dir($this->uploadDir)) {
            $this->removeDir($this->uploadDir);
        }

        if (is_dir($this->targetDir)) {
            $this->removeDir($this->targetDir);
        }

        rmdir($this->tmpDir);
    }

    public function createUploadFile()
    {
        $filename = tempnam($this->uploadDir, 'laminasc');
        file_put_contents($filename, sprintf('File created by %s', __CLASS__));

        $file = array(
            'name'     => 'test.txt',
            'type'     => 'text/plain',
            'tmp_name' => $filename,
            'size'     => filesize($filename),
            'error'    => UPLOAD_ERR_OK,
        );

        return $file;
    }

    public function removeDir($dir)
    {
        $it = new DirectoryIterator($dir);
        foreach ($it as $file) {
            if ($file->isDot()) {
                continue;
            }
            if ($file->isDir()) {
                $this->removeDir($file->getPathname());
                continue;
            }
            unlink($file->getPathname());
        }
        unset($it);
        rmdir($dir);
    }

    public function uploadMethods()
    {
        return array(
            'put'   => array('PUT'),
            'patch' => array('PATCH'),
        );
    }

    /**
     * @dataProvider uploadMethods
     */
    public function testMoveUploadedFileSucceedsOnPutAndPatchHttpRequests($method)
    {
        $target  = $this->targetDir . '/uploaded.txt';
        $file    = $this->createUploadFile();
        $request = new HttpRequest();
        $request->setMethod($method);

        $filter = new RenameUpload($target);
        $filter->setRequest($request);

        $result = $filter->filter($file);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('tmp_name', $result);
        $this->assertEquals($target, $result['tmp_name']);

        $this->assertTrue(file_exists($target));
        $this->assertFalse(file_exists($file['tmp_name']));
    }
}
