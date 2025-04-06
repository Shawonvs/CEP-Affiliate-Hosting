<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate a blog post using OpenAI or Gemini API.
 *
 * @param string $topic The topic of the blog post.
 * @param string $keywords Relevant keywords for SEO.
 * @param string $api_key The API key for OpenAI or Gemini.
 * @return string|WP_Error The generated content or WP_Error on failure.
 */
if (!function_exists('cep_generate_blog_post')) {
    function cep_generate_blog_post($topic, $keywords, $api_key) {
        $api_url = 'https://api.openai.com/v1/completions';

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'text-davinci-003',
                'prompt' => "Write a detailed blog post about '$topic' using the following keywords: $keywords. Make it SEO-friendly and engaging.",
                'max_tokens' => 1000,
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error('api_error', 'Failed to generate content. HTTP Status: ' . $status_code);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['choices'][0]['text'])) {
            return new WP_Error('json_error', 'Failed to parse API response.');
        }

        return $data['choices'][0]['text'];
    }
}

/**
 * Generate marketing decisions using OpenAI API.
 *
 * @param string $data Marketing data to analyze.
 * @param string $api_key The API key for OpenAI.
 * @return string|WP_Error The marketing decision or WP_Error on failure.
 */
if (!function_exists('cep_generate_marketing_decision')) {
    function cep_generate_marketing_decision($data, $api_key) {
        $api_url = 'https://api.openai.com/v1/completions';

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'text-davinci-003',
                'prompt' => "Analyze the following marketing data and provide actionable recommendations:\n\n$data",
                'max_tokens' => 500,
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error('api_error', 'Failed to generate marketing decision. HTTP Status: ' . $status_code);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['choices'][0]['text'])) {
            return new WP_Error('json_error', 'Failed to parse API response.');
        }

        return $data['choices'][0]['text'];
    }
}
