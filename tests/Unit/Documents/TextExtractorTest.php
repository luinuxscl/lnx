<?php

use App\Ai\DocumentExtractor\Exceptions\UnprocessableDocumentException;
use App\Ai\DocumentExtractor\Exceptions\UnsupportedFileTypeException;
use App\Ai\DocumentExtractor\TextExtractor;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->extractor = new TextExtractor;
});

it('extracts text from plain text files (.txt)', function () {
    $tempPath = sys_get_temp_dir().'/test_doc_'.uniqid().'.txt';
    file_put_contents($tempPath, 'Hello world document content');

    $result = $this->extractor->extract($tempPath);

    expect($result['text'])->toBe('Hello world document content')
        ->and($result['page_count'])->toBe(1);

    @unlink($tempPath);
});

it('throws UnsupportedFileTypeException for unsupported extensions', function () {
    $tempPath = sys_get_temp_dir().'/test_doc_'.uniqid().'.exe';
    file_put_contents($tempPath, 'Binary executable content');

    try {
        $this->extractor->extract($tempPath);
    } finally {
        @unlink($tempPath);
    }
})->throws(UnsupportedFileTypeException::class);

it('throws UnprocessableDocumentException for empty text files', function () {
    $tempPath = sys_get_temp_dir().'/test_doc_'.uniqid().'.txt';
    file_put_contents($tempPath, '   ');

    try {
        $this->extractor->extract($tempPath);
    } finally {
        @unlink($tempPath);
    }
})->throws(UnprocessableDocumentException::class);

it('throws UnprocessableDocumentException when file size exceeds limit', function () {
    config()->set('lnx_ai.document_extraction.max_file_size_kb', 1);

    $tempPath = sys_get_temp_dir().'/test_doc_'.uniqid().'.txt';
    file_put_contents($tempPath, str_repeat('A', 2048));

    try {
        $this->extractor->extract($tempPath);
    } finally {
        @unlink($tempPath);
    }
})->throws(UnprocessableDocumentException::class);
