<?php

declare(strict_types=1);

namespace Praisethedevil\ApiClientTask\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Praisethedevil\ApiClientTask\CommentsServiceApiClient;
use Praisethedevil\ApiClientTask\Dto\CreateCommentRequestDto;
use Praisethedevil\ApiClientTask\Dto\UpdateCommentRequestDto;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceNotFoundError;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceUnhandledError;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

#[CoversClass(CommentsServiceApiClient::class)]
final class CommentsServiceApiClientTest extends TestCase
{
    private ClientInterface&MockObject $httpClient;

    private MockObject&RequestInterface $request;

    private CommentsServiceApiClient $serviceApiClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->request
            ->expects($this->atLeastOnce())
            ->method('withHeader')
            ->willReturnSelf();

        $this->serviceApiClient = $this->createApiClient();
    }

    public function testCreateComment(): void
    {
        $expectedCommentId = 1;
        $this->setupSendRequestForHttpClient(200, ['id' => $expectedCommentId]);
        $this->request
            ->expects($this->once())
            ->method('withBody')
            ->willReturnSelf();

        $actualCommentId = $this->serviceApiClient->createComment(new CreateCommentRequestDto(
            name: 'name',
            text: 'comment',
        ));
        $this->assertEquals($expectedCommentId, $actualCommentId);
    }

    public function testGetComments(): void
    {
        $expectedItems = [
            [
                'id' => 1,
                'name' => 'some name',
                'text' => 'some comment',
            ],
            [
                'id' => 2,
                'name' => 'some name 2',
                'text' => 'some comment 2',
            ],
        ];
        $this->setupSendRequestForHttpClient(200, ['items' => $expectedItems]);

        $comments = $this->serviceApiClient->getComments();

        $actualItems = [];
        foreach ($comments as $comment) {
            $actualItems[] = [
                'id' => $comment->id,
                'name' => $comment->name,
                'text' => $comment->text,
            ];
        }
        $this->assertEquals($expectedItems, $actualItems);
    }

    public function testNotFoundError(): void
    {
        $this->setupSendRequestForHttpClient(404, ['error_message' => 'Not found']);

        $this->expectException(CommentsServiceNotFoundError::class);
        iterator_to_array($this->serviceApiClient->getComments());
    }

    public function testUnhandledError(): void
    {
        $this->setupSendRequestForHttpClient(500, ['error_message' => 'Server error']);

        $this->expectException(CommentsServiceUnhandledError::class);
        iterator_to_array($this->serviceApiClient->getComments());
    }

    public function testUpdateComment(): void
    {
        $updateCommentRequest = new UpdateCommentRequestDto(
            id: 1,
            name: 'Updated name',
            text: 'This is an updated comment.',
        );

        $this->setupSendRequestForHttpClient(200, [
            'id' => $updateCommentRequest->id,
            'name' => $updateCommentRequest->name,
            'text' => $updateCommentRequest->text,
        ]);
        $this->request
            ->expects($this->once())
            ->method('withBody')
            ->willReturnSelf();

        $updatedComment = $this->serviceApiClient->updateComment($updateCommentRequest);
        $this->assertEquals($updateCommentRequest->id, $updatedComment->id);
        $this->assertEquals($updateCommentRequest->name, $updatedComment->name);
        $this->assertEquals($updateCommentRequest->text, $updatedComment->text);
    }

    private function createApiClient(): CommentsServiceApiClient
    {
        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $uriFactory = $this->createMock(UriFactoryInterface::class);
        $streamFactory = $this->createMock(StreamFactoryInterface::class);

        $requestFactory
            ->expects($this->any())
            ->method('createRequest')
            ->willReturn($this->request);

        return new CommentsServiceApiClient(
            client: $this->httpClient,
            requestFactory: $requestFactory,
            uriFactory: $uriFactory,
            streamFactory: $streamFactory,
            baseUrl: 'http://example.com/',
        );
    }

    private function setupSendRequestForHttpClient(int $statusCode, array $responseBody): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(
                status: $statusCode,
                body: json_encode($responseBody, JSON_THROW_ON_ERROR),
            ));
    }
}
