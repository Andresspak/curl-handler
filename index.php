<?php
/**
 * Class CurlHandler
 *
 * @property string $host
 * @property array $queue
 */
class CurlHandler
{
    protected $host;
    protected $queue = [];

    /**
     * CurlHandler constructor.
     * @param string $host
     */
    public function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * @param string $path
     * @param array $headers
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function get($path = '', $headers = [], $params = [])
    {
        return $this->request($this->host . $path, 'GET', $headers, $params);
    }

    /**
     * @param string $path
     * @param array $headers
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function post($path = '', $headers = [], $params = [])
    {
        return $this->request($this->host . $path, 'POST', $headers, $params);
    }

    /**
     * @param string $path
     * @param array $headers
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function put($path = '', $headers = [], $params = [])
    {
        return $this->request($this->host . $path, 'PUT', $headers, $params);
    }

    /**
     * @param string $path
     * @param array $headers
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function patch($path = '', $headers = [], $params = [])
    {
        return $this->request($this->host . $path, 'PATCH', $headers, $params);
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $headers
     * @param array $params
     */
    public function queue($path = '/', $method = 'GET', $headers = [], $params = [])
    {
        $this->queue[] = $this->initialize($this->host . $path, $method, $headers, $params);
    }

    /**
     * @return array
     */
    public function run()
    {
        $cmh = curl_multi_init();

        foreach ($this->queue as $ch) {
            curl_multi_add_handle($cmh, $ch);
        }

        $active = null;
        do {
            $status = curl_multi_exec($cmh, $active);

            if ($active) {
                curl_multi_select($cmh);
            }
        } while ($active && $status == CURLM_OK);

        $responses = array_map(function ($ch) {
            $response = curl_multi_getcontent($ch);

            $responseHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $responseHeaderSize);
            $responseBody = substr($response, $responseHeaderSize);

            return [
                'headers' => $this->formatResponseHeaders($responseHeaders),
                'data' => json_decode($responseBody),
            ];
        }, $this->queue);

        foreach ($this->queue as $ch) {
            curl_multi_remove_handle($cmh, $ch);
        }

        curl_multi_close($cmh);

        return $responses;
    }

    /**
     * @param $uri string
     * @param $method string
     * @param $headers array
     * @param $params array
     * @return array
     * @throws Exception
     */
    private function request($uri, $method, $headers, $params)
    {
        $ch = $this->initialize($uri, $method, $headers, $params);

        $response = curl_exec($ch);

        $responseHeaderSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $responseHeaderSize);
        $responseBody = substr($response, $responseHeaderSize);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return [
            'headers' => $this->formatResponseHeaders($responseHeaders),
            'data' => json_decode($responseBody),
        ];
    }

    /**
     * @param string $responseHeaders
     * @return array
     */
    private function formatResponseHeaders($responseHeaders)
    {
        $headers = [];
        $requests = substr($responseHeaders, 0, strpos($responseHeaders, "\r\n\r\n"));
        foreach (explode("\r\n", $requests) as $i => $row) {
            if ($i === 0) {
                $headers['http_code'] = $row;
                continue;
            }

            list ($key, $value) = explode(': ', $row);
            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * @param string $uri
     * @param string $method
     * @param array $headers
     * @param array $params
     * @return false|resource
     */
    private function initialize($uri, $method, $headers, $params)
    {
        if ($method === 'GET') {
            $uri .= (strpos('?', $uri) === false ? '?' : '&') . http_build_query($params);
        }

        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        return $ch;
    }
}
