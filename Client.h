/*
 * Node.h
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <iostream>
#include <errno.h>
#include <string.h>

#ifndef CLIENT_H_
#define CLIENT_H_

class Client {
private:
	int nodeID;
	int soketID; //Using this as both the socket and uniq id
	sockaddr_in nodeSocket; //Info about socket i think
	int nodeSocketLength;
	bool handshake; //Has the handshake happened
	int specification; //0=none 1=RFC6455
	char* key;

	void parseKey(char *, int);

public:
	Client(int);
	virtual ~Client();

	int getID();
};

#endif /* CLIENT_H_ */
