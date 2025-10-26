<?php

namespace ProcessFlows\OpenRouter;

use WordPress\AiClient\Common\Exception\RuntimeException;
use WordPress\AiClient\Providers\ApiBasedImplementation\AbstractApiProvider;
use WordPress\AiClient\Providers\ApiBasedImplementation\ListModelsApiBasedProviderAvailability;
use WordPress\AiClient\Providers\Contracts\ModelMetadataDirectoryInterface;
use WordPress\AiClient\Providers\Contracts\ProviderAvailabilityInterface;
use WordPress\AiClient\Providers\DTO\ProviderMetadata;
use WordPress\AiClient\Providers\Enums\ProviderTypeEnum;
use WordPress\AiClient\Providers\Models\Contracts\ModelInterface;
use WordPress\AiClient\Providers\Models\DTO\ModelMetadata;

/**
 * Class for the OpenAI provider.
 *
 * @since 0.1.0
 */
class OpenRouterProvider extends AbstractApiProvider
{
    /**
     * {@inheritDoc}
     *
     * @since 0.2.0
     */
    protected static function baseUrl(): string
    {
        return 'https://openrouter.ai/api/v1';
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createModel(
        ModelMetadata $modelMetadata,
        ProviderMetadata $providerMetadata
    ): ModelInterface {
        $capabilities = $modelMetadata->getSupportedCapabilities();
        foreach ($capabilities as $capability) {
            if ($capability->isTextGeneration()) {
                return new OpenRouterTextGenerationModel($modelMetadata, $providerMetadata);
            }
            if ($capability->isImageGeneration()) {
                // TODO: Implement.
                throw new RuntimeException(
                    'OpenRouter image generation model class is not yet implemented.'
                );
            }
            if ($capability->isTextToSpeechConversion()) {
                // TODO: Implement OpenRouterTextToSpeechConversionModel.
                throw new RuntimeException(
                    'OpenRouter text to speech conversion model class is not yet implemented.'
                );
            }
        }

        throw new RuntimeException(
            'Unsupported model capabilities: '.implode(', ', $capabilities)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createProviderMetadata(): ProviderMetadata
    {
        return new ProviderMetadata(
            'openrouter',
            'OpenRouter',
            ProviderTypeEnum::cloud()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createProviderAvailability(): ProviderAvailabilityInterface
    {
        // Check valid API access by attempting to list models.
        return new ListModelsApiBasedProviderAvailability(
            static::modelMetadataDirectory()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @since 0.1.0
     */
    protected static function createModelMetadataDirectory(): ModelMetadataDirectoryInterface
    {
        return new OpenRouterModelMetadataDirectory();
    }
}
