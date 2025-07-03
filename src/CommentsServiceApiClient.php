<?php

declare(strict_types=1);

namespace Praisethedevil\ApiClientTask;

use Generator;
use JsonException;
use Praisethedevil\ApiClientTask\Dto\CommentDto;
use Praisethedevil\ApiClientTask\Dto\CreateCommentRequestDto;
use Praisethedevil\ApiClientTask\Dto\UpdateCommentRequestDto;
use Praisethedevil\ApiClientTask\Errors\AbstractCommentsServiceError;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceNotFoundError;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceRequestBodyEncodeError;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceResponseDecodeError;
use Praisethedevil\ApiClientTask\Errors\CommentsServiceUnhandledError;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * @psalm-type CommentObject = array{
 *     id: int,
 *     name: string,
 *     text: string,
 * }
 * @psalm-type GetCommentsResponse = array{
 *     items: list<CommentObject>,
 * }
 * @psalm-type CreateCommentResponse = array{id: int}
 */
class CommentsServiceApiClient
{
    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly RequestFactoryInterface $requestFactory,
        protected readonly UriFactoryInterface $uriFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly string $baseUrl,
    ) {}

    /** @throws AbstractCommentsServiceError */
    public function createComment(CreateCommentRequestDto $request): int
    {
        $response = $this->makeRequest(method: 'POST', path: 'comment', body: [
            'name' => $request->name,
            'text' => $request->text,
        ]);

        /** @var CreateCommentResponse $responseData */
        $responseData = $this->decodeJsonResponse($response->getBody()->getContents());

        return $responseData['id'];
    }

    /**
     * @return Generator<CommentDto>
     *
     * @throws AbstractCommentsServiceError
     */
    public function getComments(): Generator
    {
        $response = $this->makeRequest(method: 'GET', path: 'comments', body: []);

        /** @var GetCommentsResponse $responseData */
        $responseData = $this->decodeJsonResponse($response->getBody()->getContents());

        foreach ($responseData['items'] as $commentData) {
            yield $this->buildComment($commentData);
        }
    }

    /** @throws AbstractCommentsServiceError */
    public function updateComment(UpdateCommentRequestDto $request): CommentDto
    {
        $response = $this->makeRequest(method: 'PUT', path: "comment/{$request->id}", body: [
            'name' => $request->name,
            'text' => $request->text,
        ]);

        /** @var CommentObject $responseData */
        $responseData = $this->decodeJsonResponse($response->getBody()->getContents());

        return $this->buildComment($responseData);
    }

    protected function buildRequest(string $method, string $path, array $body): RequestInterface
    {
        $uri = $this->uriFactory->createUri($this->baseUrl . $path);

        $request = $this->requestFactory
            ->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json');

        if (in_array($method, ['POST', 'PUT'], true)) {
            try {
                $encodedBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new CommentsServiceRequestBodyEncodeError($exception->getMessage(), previous: $exception);
            }

            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($encodedBody));
        }

        return $request;
    }

    /** @return array<array-key, mixed> */
    protected function decodeJsonResponse(string $content): array
    {
        try {
            $decoded = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) {
                throw new CommentsServiceResponseDecodeError(
                    'Expected an associative array from JSON response',
                );
            }

            return $decoded;
        } catch (JsonException $exception) {
            throw new CommentsServiceResponseDecodeError(
                'Failed to decode JSON response',
                previous: $exception,
            );
        }
    }

    protected function processResponse(ResponseInterface $response): ResponseInterface
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 299) {
            $errorMessage = $response->getBody()->getContents();

            throw match ($statusCode) {
                404 => new CommentsServiceNotFoundError(message: $errorMessage),
                default => new CommentsServiceUnhandledError(message: $errorMessage),
            };
        }

        return $response;
    }

    /** @param CommentObject $commentData */
    private function buildComment(array $commentData): CommentDto
    {
        return new CommentDto(
            id: $commentData['id'],
            name: $commentData['name'],
            text: $commentData['text'],
        );
    }

    private function makeRequest(string $method, string $path, array $body): ResponseInterface
    {
        $request = $this->buildRequest($method, $path, $body);

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new CommentsServiceUnhandledError($exception->getMessage(), previous: $exception);
        }

        return $this->processResponse($response);
    }
}
